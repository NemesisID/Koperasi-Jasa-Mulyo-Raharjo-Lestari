<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Member;
use App\Models\SetoranKoperasi;
use App\Models\User;
use App\Repositories\Contracts\SetoranKoperasiRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SavingsService
{
    /**
     * Iuran bulanan: total Rp50.000 → operasional Rp45.000 + simpanan Rp5.000.
     */
    public const WAJIB_MONTHLY = 5000;
    public const TIPPING_MONTHLY = 40000;
    public const OPERASIONAL_MONTHLY = 45000;
    public const WAJIB_TOTAL_MONTHLY = 50000;

    /**
     * Map label setoran -> kategori jurnal kas.
     */
    private const LABEL_CATEGORY = [
        'POKOK' => 'Simpanan Pokok',
        'WAJIB' => 'Simpanan Wajib',
        'SUKARELA' => 'Simpanan Sukarela',
        'TIPPING' => 'Tipping Fee Pengangkutan',
    ];

    public function __construct(
        private readonly SetoranKoperasiRepositoryInterface $setoranRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    /**
     * Catat pembayaran simpanan/tipping: setoran SELESAI + jurnal income kas.
     */
    public function recordSavingsPayment(array $data, User $handler): SetoranKoperasi
    {
        $member = Member::findOrFail($data['member_id']);

        if ($member->status !== 'aktif') {
            throw new BusinessLogicException('Anggota tidak aktif — pembayaran simpanan tidak dapat dicatat.');
        }

        // Pembayaran WAJIB bulanan Rp50.000: dipecah otomatis menjadi
        // operasional Rp45.000 (label TIPPING) + simpanan Rp5.000 (label WAJIB).
        if ($data['label'] === 'WAJIB' && (float) $data['jumlah'] === (float) self::WAJIB_TOTAL_MONTHLY) {
            return $this->payMonthlyWajib($member, $handler, $data['metode'], $data['catatan'] ?? null);
        }

        return DB::transaction(function () use ($data, $handler, $member): SetoranKoperasi {
            $setoran = $this->setoranRepository->create([
                'user_id' => $member->user_id,
                'jenis' => 'PEMASUKAN',
                'jumlah' => $data['jumlah'],
                'status' => 'SELESAI',
                'label' => $data['label'],
                'catatan' => $data['catatan'] ?? null,
            ]);

            $this->transactionRepository->create([
                'member_id' => $member->id,
                'category_id' => $this->transactionRepository->findCategoryIdByName(self::LABEL_CATEGORY[$data['label']]),
                'type' => 'income',
                'amount' => $data['jumlah'],
                'description' => "Setoran {$data['label']} anggota {$member->member_code}".(isset($data['catatan']) ? " — {$data['catatan']}" : ''),
                'payment_method' => $data['metode'],
                'status' => 'berhasil',
                'handled_by' => $handler->id,
            ]);

            return $setoran;
        });
    }

    /**
     * Konfirmasi pembayaran setoran wajib Rp50.000 oleh pengurus:
     * tandai tagihan WAJIB + TIPPING bulan berjalan sebagai SELESAI (atau buat baru),
     * lalu jurnal kas 45.000 operasional + 5.000 simpanan.
     */
    private function payMonthlyWajib(Member $member, User $handler, string $metode, ?string $catatan): SetoranKoperasi
    {
        return DB::transaction(function () use ($member, $handler, $metode, $catatan): SetoranKoperasi {
            $setoran = null;

            foreach (['TIPPING' => self::OPERASIONAL_MONTHLY, 'WAJIB' => self::WAJIB_MONTHLY] as $label => $amount) {
                // Lunasi tagihan PENDING bulan berjalan jika ada; jika tidak, buat baris SELESAI baru.
                $setoran = SetoranKoperasi::where('user_id', $member->user_id)
                    ->where('label', $label)
                    ->where('jenis', 'PEMASUKAN')
                    ->whereIn('status', ['PENDING', 'PROSES'])
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->lockForUpdate()
                    ->first();

                if ($setoran) {
                    $setoran->update(['status' => 'SELESAI', 'jumlah' => $amount, 'catatan' => $catatan ?? $setoran->catatan]);
                } else {
                    $setoran = $this->setoranRepository->create([
                        'user_id' => $member->user_id,
                        'jenis' => 'PEMASUKAN',
                        'jumlah' => $amount,
                        'status' => 'SELESAI',
                        'label' => $label,
                        'catatan' => $catatan ?? "Setoran wajib bulan ".now()->format('Y-m'),
                    ]);
                }

                $this->transactionRepository->create([
                    'member_id' => $member->id,
                    'category_id' => $this->transactionRepository->findCategoryIdByName(self::LABEL_CATEGORY[$label]),
                    'type' => 'income',
                    'amount' => $amount,
                    'description' => "Setoran wajib ({$label}) anggota {$member->member_code}".($catatan ? " — {$catatan}" : ''),
                    'payment_method' => $metode,
                    'status' => 'berhasil',
                    'handled_by' => $handler->id,
                ]);
            }

            return $setoran;
        });
    }

    /**
     * Overview setoran wajib seluruh anggota untuk halaman pengurus:
     * per anggota — status tagihan bulan berjalan (WAJIB + TIPPING dianggap satu paket Rp50.000).
     * ponytail: N+1 ringan per anggota (2 query) — cache/aggregate query jika anggota ribuan.
     */
    public function getWajibOverview(): array
    {
        $month = now()->format('Y-m');

        $rows = Member::where('status', 'aktif')
            ->with('user:id,name,username')
            ->orderBy('name')
            ->get(['id', 'user_id', 'member_code', 'name', 'phone'])
            ->map(function (Member $member) use ($month): array {
                $paid = SetoranKoperasi::where('user_id', $member->user_id)
                    ->whereIn('label', ['WAJIB', 'TIPPING'])
                    ->where('jenis', 'PEMASUKAN')
                    ->where('status', 'SELESAI')
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->exists();

                return [
                    'member_id' => $member->id,
                    'member_code' => $member->member_code,
                    'name' => $member->name,
                    'phone' => $member->phone,
                    'username' => $member->user?->username,
                    'period' => $month,
                    'tagihan_operasional' => self::OPERASIONAL_MONTHLY,
                    'tagihan_simpanan' => self::WAJIB_MONTHLY,
                    'tagihan_total' => self::WAJIB_TOTAL_MONTHLY,
                    'status' => $paid ? 'LUNAS' : 'BLM BAYAR',
                ];
            });

        return [
            'period' => $month,
            'tagihan_operasional' => self::OPERASIONAL_MONTHLY,
            'tagihan_simpanan' => self::WAJIB_MONTHLY,
            'tagihan_total' => self::WAJIB_TOTAL_MONTHLY,
            'belum_bayar' => $rows->where('status', 'BLM BAYAR')->count(),
            'lunas' => $rows->where('status', 'LUNAS')->count(),
            'members' => $rows->values(),
        ];
    }

    /**
     * Tagihan massal bulan berjalan untuk seluruh anggota aktif yang belum bayar.
     * Tagihan = baris PENDING per label (WAJIB 5.000 + TIPPING 40.000).
     */
    public function generateMonthlyBilling(): array
    {
        $month = now()->format('Y-m');
        $created = 0;

        DB::transaction(function () use ($month, &$created): void {
            Member::where('status', 'aktif')->with('user:id')->chunkById(100, function ($members) use ($month, &$created): void {
                foreach ($members as $member) {
                    foreach (['WAJIB' => self::WAJIB_MONTHLY, 'TIPPING' => self::TIPPING_MONTHLY] as $label => $amount) {
                        $exists = SetoranKoperasi::where('user_id', $member->user_id)
                            ->where('label', $label)
                            ->where('jenis', 'PEMASUKAN')
                            ->whereYear('created_at', now()->year)
                            ->whereMonth('created_at', now()->month)
                            ->exists();

                        if (! $exists) {
                            $this->setoranRepository->create([
                                'user_id' => $member->user_id,
                                'jenis' => 'PEMASUKAN',
                                'jumlah' => $amount,
                                'status' => 'PENDING',
                                'label' => $label,
                                'catatan' => "Tagihan rutin bulan {$month}",
                            ]);
                            $created++;
                        }
                    }
                }
            });
        });

        return ['period' => $month, 'invoices_created' => $created];
    }

    /**
     * Status tagihan bulan berjalan seorang anggota: lunas / tunggakan.
     */
    public function getBillingStatus(int $memberId): array
    {
        $member = Member::with('user:id')->findOrFail($memberId);

        $paid = SetoranKoperasi::where('user_id', $member->user_id)
            ->where('jenis', 'PEMASUKAN')
            ->whereIn('label', ['WAJIB', 'TIPPING'])
            ->whereIn('status', ['SELESAI', 'PROSES', 'PENDING'])
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->get(['label', 'jumlah', 'status']);

        $labels = [
            'WAJIB' => self::WAJIB_MONTHLY,
            'TIPPING' => self::TIPPING_MONTHLY,
        ];

        $detail = collect($labels)->map(function (int $amount, string $label) use ($paid): array {
            // Utamakan baris SELESAI (pembayaran); baris lain (tagihan) hanya sebagai info status
            $row = $paid->firstWhere(fn ($r) => $r->label === $label && $r->status === 'SELESAI')
                ?? $paid->firstWhere('label', $label);

            return [
                'label' => $label,
                'billed' => $amount,
                'paid' => $row?->status === 'SELESAI',
                'status' => $row?->status ?? 'BELUM_TAGIH',
                'amount' => (float) $row?->jumlah,
            ];
        })->values();

        return [
            'member' => ['id' => $member->id, 'member_code' => $member->member_code, 'name' => $member->name],
            'period' => now()->format('Y-m'),
            'total_monthly' => self::WAJIB_MONTHLY + self::TIPPING_MONTHLY,
            'fully_paid' => $detail->every(fn ($d) => $d['paid']),
            'detail' => $detail,
        ];
    }

    public function getSavings(array $filters): LengthAwarePaginator
    {
        return $this->setoranRepository->paginate($filters);
    }

    /**
     * Simpanan pokok otomatis Rp50.000 saat akun anggota baru dibuat.
     */
    public function recordInitialPokok(Member $member): void
    {
        $this->setoranRepository->create([
            'user_id' => $member->user_id,
            'jenis' => 'PEMASUKAN',
            'jumlah' => 50000,
            'status' => 'SELESAI',
            'label' => 'POKOK',
            'catatan' => 'Simpanan pokok otomatis saat pendaftaran anggota',
        ]);
    }
}
