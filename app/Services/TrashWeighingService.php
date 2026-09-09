<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Pickup;
use App\Models\TrashCategory;
use App\Models\User;
use App\Repositories\Contracts\MemberRepositoryInterface;
use App\Repositories\Contracts\PickupRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\TrashCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TrashWeighingService
{
    public function __construct(
        private readonly PickupRepositoryInterface $pickupRepository,
        private readonly TrashCategoryRepositoryInterface $trashCategoryRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly MemberRepositoryInterface $memberRepository,
        private readonly TrashCategoryService $trashCategoryService,
    ) {}

    public function getPickups(array $filters): LengthAwarePaginator
    {
        return $this->pickupRepository->paginate($filters);
    }

    public function getPickup(int $id): Pickup
    {
        return $this->pickupRepository->findByIdWithDetails($id);
    }

    /**
     * Buat tiket setor/jemput sampah (status menunggu timbang).
     * Anggota tanpa member_id → otomatis member miliknya (pesan antar ala gojek).
     */
    public function createTicket(array $data, User $creator): Pickup
    {
        $memberId = $data['member_id']
            ?? ($creator->role === 'anggota' ? $creator->member?->id : null);

        if (! $memberId) {
            throw new BusinessLogicException('member_id wajib diisi untuk akun non-anggota.');
        }

        $member = $this->memberRepository->findById($memberId);

        if ($member->status !== 'aktif') {
            throw new BusinessLogicException('Anggota tidak aktif — transaksi setor sampah tidak dapat dibuat.');
        }

        // Anggota hanya boleh membuat tiket untuk dirinya sendiri
        if ($creator->role === 'anggota' && $member->user_id !== $creator->id) {
            throw new BusinessLogicException('Anda hanya dapat membuat tiket untuk anggota atas nama Anda sendiri.');
        }

        return $this->pickupRepository->createHeader([
            'member_id' => $member->id,
            'officer_id' => in_array($creator->role, ['petugas', 'pengurus', 'ketua']) ? $creator->id : null,
            'location_type' => $data['location_type'],
            'is_sorted' => $data['is_sorted'] ?? false,
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'notes' => $data['notes'] ?? null,
            'source' => $data['source'] ?? 'manual',
            'status' => 'menunggu',
        ]);
    }

    /**
     * Core engine: hitung nilai per item dari harga jual raw (`price_sell`),
     * potong biaya admin manual per kategori (`price_admin`, guideline 20%),
     * kredit harga bersih ke anggota (total_net), dan catat jurnal kas — satu transaksi DB.
     *
     * @param  array<int, array{category_id: int, weight_kg?: float, unit_count?: int}>  $items
     */
    public function weighAndComplete(int $pickupId, array $items, User $officer): Pickup
    {
        return DB::transaction(function () use ($pickupId, $items, $officer): Pickup {
            $pickup = $this->lockPickup($pickupId);

            if ($pickup->status !== 'menunggu') {
                throw new BusinessLogicException("Transaksi timbang tidak dapat diproses: status saat ini '{$pickup->status}'.");
            }

            $itemRows = [];
            $totalGross = 0.0;
            $totalFee = 0.0;

            foreach ($items as $item) {
                // ponytail: kategori di-lock supaya harga tidak berubah di tengah penimbangan; batch fetch jika item banyak.
                $category = TrashCategory::lockForUpdate()->findOrFail($item['category_id']);

                $unitPrice = $pickup->location_type === 'jemput_rumah'
                    ? $this->trashCategoryService->pickupPrice($category)
                    : (float) $category->price_sell;

                $quantity = $category->unit === 'kg'
                    ? (float) ($item['weight_kg'] ?? 0)
                    : (int) ($item['unit_count'] ?? 0);

                if ($quantity <= 0) {
                    throw new BusinessLogicException("Item '{$category->name}' tidak memiliki kuantitas (berat/jumlah harus lebih dari 0).");
                }

                $lineValue = round($quantity * $unitPrice, 2);
                $lineFee = round($quantity * (float) $category->price_admin, 2);
                $totalGross += $lineValue;
                $totalFee += $lineFee;

                $itemRows[] = [
                    'category_id' => $category->id,
                    'weight_kg' => $category->unit === 'kg' ? $quantity : 0,
                    'unit_count' => $category->unit === 'kg' ? 0 : (int) $quantity,
                    'total_value' => $lineValue,
                ];
            }

            $totalGross = round($totalGross, 2);
            $totalFee = round($totalFee, 2);
            $totalNet = round($totalGross - $totalFee, 2);

            // Jurnal kas: biaya admin masuk sebagai pemasukan koperasi
            $feeCategoryId = $this->transactionRepository->findCategoryIdByName('Potongan Admin Sampah 20%');
            $feeTransaction = $this->transactionRepository->create([
                'member_id' => $pickup->member_id,
                'category_id' => $feeCategoryId,
                'type' => 'income',
                'amount' => $totalFee,
                'description' => "Potongan biaya admin transaksi timbang #{$pickup->id}",
                'payment_method' => 'sampah',
                'status' => 'berhasil',
                'handled_by' => $officer->id,
            ]);

            $this->pickupRepository->addItemsAndComplete($pickup, $itemRows, $feeTransaction, $totalGross, $totalFee, $totalNet);

            return $this->pickupRepository->findByIdWithDetails($pickup->id);
        });
    }

    /**
     * Batalkan transaksi timbang selesai: rollback jurnal 20% dan saldo (via status).
     */
    public function cancelPickup(int $pickupId, string $reason): Pickup
    {
        return DB::transaction(function () use ($pickupId, $reason): Pickup {
            $pickup = $this->lockPickup($pickupId);

            if ($pickup->status === 'batal') {
                throw new BusinessLogicException('Transaksi timbang ini sudah dibatalkan sebelumnya.');
            }

            if ($pickup->status !== 'selesai') {
                throw new BusinessLogicException('Hanya transaksi timbang berstatus selesai yang dapat dibatalkan.');
            }

            // Rollback jurnal kas: tandai transaksi fee 20% gagal
            $pickup->load('items')->items->pluck('transaction_id')->filter()->unique()->each(
                fn ($transactionId) => $this->transactionRepository->updateStatus($transactionId, 'gagal'),
            );

            $this->pickupRepository->cancel($pickup, $reason);

            return $this->pickupRepository->findByIdWithDetails($pickup->id);
        });
    }

    private function lockPickup(int $pickupId): Pickup
    {
        return Pickup::lockForUpdate()->findOrFail($pickupId);
    }
}
