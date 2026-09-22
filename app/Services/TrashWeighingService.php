<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Member;
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

    /**
     * Rekap status penjemputan dari seluruh data — sumber angka tunggal
     * untuk dashboard pengurus maupun petugas.
     */
    public function getStatusCounts(): array
    {
        return $this->pickupRepository->statusCounts();
    }

    /**
     * Susun ulang urutan antrean penjemputan (R23) — validasi sudah di controller.
     */
    public function reorderPickups(array $ids): void
    {
        $this->pickupRepository->reorder($ids);
    }

    public function getPickup(int $id): Pickup
    {
        return $this->pickupRepository->findByIdWithDetails($id);
    }

    /**
     * Buat tiket setor/jemput sampah (status menunggu timbang).
     */
    public function createTicket(array $data, User $creator): Pickup
    {
        $member = $this->memberRepository->findById($data['member_id']);

        if ($member->status !== 'aktif') {
            throw new BusinessLogicException('Anggota tidak aktif — transaksi setor sampah tidak dapat dibuat.');
        }

        // Anggota hanya boleh membuat tiket untuk dirinya sendiri
        if ($creator->role === 'anggota' && $member->user_id !== $creator->id) {
            throw new BusinessLogicException('Anda hanya dapat membuat tiket untuk anggota atas nama Anda sendiri.');
        }

        return $this->pickupRepository->createHeader([
            'member_id' => $member->id,
            'officer_id' => in_array($creator->role, ['petugas', 'pengurus']) ? $creator->id : null,
            'location_type' => $data['location_type'],
            'is_sorted' => $data['is_sorted'] ?? false,
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'notes' => $data['notes'] ?? null,
            'status' => 'menunggu',
        ]);
    }

    /**
     * Tiket penjemputan harian untuk satu anggota (dipakai scheduler & saat
     * akun anggota dibuat): satu tiket per kategori alamat — rumah →
     * jemput_rumah, pasar → jemput_pasar. Anggota dual-status (rumah+pasar)
     * dapat dua tiket dalam sehari.
     *
     * Petugas mengikuti plotting anggota. Anggota tanpa plotting tetap dapat
     * tiket (officer_id null) supaya pengurus bisa menugaskan manual — bukan
     * hilang dari daftar harian.
     *
     * @return list<Pickup>
     */
    public function createDailyTickets(Member $member, string $date, string $notes = 'Jadwal harian otomatis'): array
    {
        // Anggota nonaktif tidak dijemput — scheduler juga menyaringnya di query.
        if ($member->status !== 'aktif') {
            return [];
        }

        $types = $member->categories ?: [$member->category?->name ?? 'rumah'];

        // Satu tiket per lokasi unik; kategori di luar rumah/pasar memakai rute rumah.
        $locations = array_values(array_unique(array_map(
            fn (string $type): string => $type === 'pasar' ? 'jemput_pasar' : 'jemput_rumah',
            array_map(strval(...), $types),
        )));

        $tickets = [];
        foreach ($locations as $locationType) {
            $tickets[] = $this->pickupRepository->createHeader([
                'member_id' => $member->id,
                'officer_id' => $member->officer_id,
                'location_type' => $locationType,
                'is_sorted' => false,
                'scheduled_at' => $date.' 07:00:00',
                'notes' => $notes,
                'status' => 'menunggu',
            ]);
        }

        return $tickets;
    }

    /**
     * Core engine: hitung nilai per item dari harga anggota katalog (sudah termasuk
     * potongan 20% koperasi), kredit penuh ke anggota, dan catat jurnal kas —
     * satu transaksi DB.
     *
     * @param  array<int, array{category_id: int, weight_kg?: float, unit_count?: int}>  $items
     */
    public function weighAndComplete(int $pickupId, array $items, User $officer): Pickup
    {
        return DB::transaction(function () use ($pickupId, $items, $officer): Pickup {
            $pickup = $this->lockPickup($pickupId);

            if ($pickup->status === 'batal') {
                throw new BusinessLogicException("Transaksi timbang tidak dapat diproses: status saat ini '{$pickup->status}'.");
            }

            // Edit timbangan: tiket selesai boleh ditimbang ulang — jurnal lama
            // dibatalkan dan item diganti. Saldo anggota derived dari total_net
            // pickup sehingga ikut terkoreksi otomatis.
            if ($pickup->status === 'selesai') {
                $this->rollbackWeighJournal($pickup);
            }

            $itemRows = [];
            $totalGross = 0.0;

            foreach ($items as $item) {
                // ponytail: kategori di-lock supaya harga tidak berubah di tengah penimbangan; batch fetch jika item banyak.
                $category = TrashCategory::lockForUpdate()->findOrFail($item['category_id']);

                // Lokasi jemput (rumah/pasar) pakai tarif jemput (harga anggota − ongkos);
                // gudang pakai harga anggota katalog langsung.
                $unitPrice = str_starts_with($pickup->location_type, 'jemput')
                    ? $this->trashCategoryService->pickupPrice($category, $pickup->is_sorted)
                    : ($pickup->is_sorted ? (float) $category->price_member : (float) $category->price_member_unsorted);

                $quantity = $category->unit === 'kg'
                    ? (float) ($item['weight_kg'] ?? 0)
                    : (int) ($item['unit_count'] ?? 0);

                if ($quantity <= 0) {
                    throw new BusinessLogicException("Item '{$category->name}' tidak memiliki kuantitas (berat/jumlah harus lebih dari 0).");
                }

                $lineValue = round($quantity * $unitPrice, 2);
                $totalGross += $lineValue;

                $itemRows[] = [
                    'category_id' => $category->id,
                    'weight_kg' => $category->unit === 'kg' ? $quantity : 0,
                    'unit_count' => $category->unit === 'kg' ? 0 : (int) $quantity,
                    'total_value' => $lineValue,
                ];
            }

            $totalGross = round($totalGross, 2);
            // Potongan 20% sudah melekat di harga anggota katalog (price_member =
            // 80% harga jual) — tidak dipotong lagi di timbangan. Kolom total_fee
            // tetap ada demi data timbangan lama; yang baru selalu 0.
            $totalNet = $totalGross;

            // Jurnal kas: nilai bersih yang dibayarkan ke anggota = biaya beli sampah
            // (expense koperasi, terpotong otomatis dari kas saat membeli sampah anggota).
            // Tidak ada lagi jurnal income "Potongan Admin Sampah 20%" — margin koperasi
            // kini melekat pada selisih harga jual vs harga beli, bukan potongan di timbangan.
            $buyCategoryId = $this->transactionRepository->findCategoryIdByName('Beli Sampah Anggota');
            $purchaseTransaction = $this->transactionRepository->create([
                'member_id' => $pickup->member_id,
                'category_id' => $buyCategoryId,
                'type' => 'expense',
                'amount' => $totalNet,
                'description' => "Beli sampah anggota #{$pickup->id}",
                'payment_method' => 'sampah',
                'status' => 'berhasil',
                'handled_by' => $officer->id,
            ]);

            $this->pickupRepository->addItemsAndComplete($pickup, $itemRows, $purchaseTransaction, $totalGross, 0.0, $totalNet, $officer);

            return $this->pickupRepository->findByIdWithDetails($pickup->id);
        });
    }

    /**
     * Batalkan transaksi timbang selesai: rollback jurnal beli sampah dan saldo (via status).
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

            // Rollback jurnal kas lama (fee 20% + beli sampah anggota)
            $this->rollbackWeighJournal($pickup);

            $this->pickupRepository->cancel($pickup, $reason);

            return $this->pickupRepository->findByIdWithDetails($pickup->id);
        });
    }

    private function lockPickup(int $pickupId): Pickup
    {
        return Pickup::lockForUpdate()->findOrFail($pickupId);
    }

    /**
     * Batalkan jurnal kas satu timbangan. Tiap item menunjuk jurnal beli sampah
     * miliknya, jadi tidak perlu lagi mencari berdasarkan deskripsi.
     */
    private function rollbackWeighJournal(Pickup $pickup): void
    {
        $pickup->load('items')->items->pluck('transaction_id')->filter()->unique()->each(
            fn ($transactionId) => $this->transactionRepository->updateStatus($transactionId, 'gagal'),
        );

        $pickup->items()->delete();
    }
}
