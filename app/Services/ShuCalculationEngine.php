<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Member;
use App\Models\Pickup;
use App\Models\SetoranKoperasi;
use App\Models\ShuDistribution;
use App\Models\ShuMember;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\ShuDistributionRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ShuCalculationEngine
{
    /**
     * Alokasi pool SHU: 50% jasa modal (simpanan), 50% jasa partisipasi (transaksi sampah).
     * ponytail: rasio 50/50 asumsi — anggap dari rapat anggota jika PRD merinci.
     */
    private const MODAL_SHARE = 0.5;

    public function __construct(
        private readonly ShuDistributionRepositoryInterface $shuRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    /**
     * Simulasi distribusi: hitung pool 20% laba bersih dan preview dividen per anggota.
     * Tidak menulis apa pun ke tabel shu_members — hanya preview.
     *
     * @return array{year: int, net_profit: float, shu_pool: float, modal_pool: float, participation_pool: float, total_distributed: float, members: array<int, array<string, mixed>>}
     */
    public function simulateDistribution(int $year, float $netProfit, float $poolPercentage = 20): array
    {
        if ($year !== (int) now()->year && ShuDistribution::where('year', $year)->where('status', 'dibagikan')->exists()) {
            throw new BusinessLogicException("SHU tahun {$year} sudah dibagikan — tidak dapat disimulasikan ulang.");
        }

        $shuPool = round($netProfit * ($poolPercentage / 100), 2);
        $modalPool = round($shuPool * self::MODAL_SHARE, 2);
        $participationPool = round($shuPool - $modalPool, 2);

        // Dibagi RATA per member: 1 porsi per kategori member (dual-status rumah+pasar = 2 porsi).
        $members = Member::where('status', 'aktif')->with('user:id')->get(['id', 'member_code', 'name', 'user_id', 'categories']);

        $simpanan = SetoranKoperasi::whereIn('user_id', $members->pluck('user_id'))
            ->whereIn('label', ['POKOK', 'WAJIB', 'SUKARELA'])
            ->where('status', 'SELESAI')
            ->whereYear('created_at', $year)
            ->selectRaw('user_id, SUM(jumlah) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $participation = Pickup::where('status', 'selesai')
            ->whereYear('completed_at', $year)
            ->selectRaw('member_id, SUM(total_gross) as total')
            ->groupBy('member_id')
            ->pluck('total', 'member_id');

        $shares = $members->sum(fn (Member $m) => max(1, count($m->categories ?? [])));
        if ($shares < 1) {
            throw new BusinessLogicException('Tidak ada anggota aktif untuk menerima SHU.');
        }

        $rows = [];
        $totalDistributed = 0.0;

        foreach ($members as $member) {
            // Porsi anggota = jumlah kategori member (rumah/pasar), dibagi rata dari pool.
            $memberShares = max(1, count($member->categories ?? []));

            $modalAmount = round($modalPool * ($memberShares / $shares), 2);
            $participationShu = round($participationPool * ($memberShares / $shares), 2);
            $total = round($modalAmount + $participationShu, 2);

            $rows[] = [
                'member_id' => $member->id,
                'member_code' => $member->member_code,
                'name' => $member->name,
                'simpanan_amount' => (float) ($simpanan[$member->user_id] ?? 0),
                'participation_amount' => (float) ($participation[$member->id] ?? 0),
                'simpanan_pokok_amount' => $modalAmount,
                'simpanan_wajib_amount' => 0.0, // dipecah saat publish jika diperlukan
                'participation_shu' => $participationShu,
                'total_shu' => $total,
            ];
            $totalDistributed += $total;
        }

        // Sisa pembulatan masuk cadangan
        $reserve = round($shuPool - $totalDistributed, 2);

        return [
            'year' => $year,
            'net_profit' => $netProfit,
            'shu_pool' => $shuPool,
            'modal_pool' => $modalPool,
            'participation_pool' => $participationPool,
            'total_distributed' => round($totalDistributed, 2),
            'reserve_amount' => $reserve,
            'recipient_count' => count($rows),
            'members' => $rows,
        ];
    }

    /**
     * Publish: kunci distribusi jadi dibagikan, buat shu_members, kredit saldo
     * (derived — saldo otomatis naik via tabel shu_members), catat expense kas.
     */
    public function publishDistribution(int $distributionId, User $authorizer): ShuDistribution
    {
        return DB::transaction(function () use ($distributionId, $authorizer): ShuDistribution {
            $distribution = ShuDistribution::lockForUpdate()->findOrFail($distributionId);

            if ($distribution->status === 'dibagikan') {
                throw new BusinessLogicException("SHU tahun {$distribution->year} sudah dibagikan sebelumnya.");
            }

            $shuMembers = ShuMember::where('shu_distribution_id', $distribution->id)->get();

            if ($shuMembers->isEmpty()) {
                throw new BusinessLogicException('Draft SHU ini belum memiliki rincian per anggota.');
            }

            $expenseCategoryId = $this->transactionRepository->findCategoryIdByName('Distribusi SHU Anggota');

            foreach ($shuMembers as $shuMember) {
                if ($shuMember->status === 'sudah_dibagikan') {
                    continue;
                }

                $transaction = $this->transactionRepository->create([
                    'member_id' => $shuMember->member_id,
                    'category_id' => $expenseCategoryId,
                    'type' => 'expense',
                    'amount' => $shuMember->total_shu,
                    'description' => "Dividen SHU tahun {$distribution->year} ({$shuMember->total_shu})",
                    'payment_method' => 'tunai',
                    'status' => 'berhasil',
                    'handled_by' => $authorizer->id,
                ]);

                $shuMember->update([
                    'status' => 'sudah_dibagikan',
                    'paid_at' => now(),
                    'transaction_id' => $transaction->id,
                ]);
            }

            $distribution->update([
                'status' => 'dibagikan',
                'distribution_date' => now()->toDateString(),
                'distributed_amount' => $shuMembers->sum('total_shu'),
                'recipient_count' => $shuMembers->count(),
            ]);

            return $distribution->fresh();
        });
    }

    /**
     * Simpan hasil simulasi sebagai draft distribusi (belum memotong kas apa pun).
     */
    public function saveDraft(array $simulation, User $authorizer): ShuDistribution
    {
        return DB::transaction(function () use ($simulation, $authorizer): ShuDistribution {
            if (ShuDistribution::where('year', $simulation['year'])->exists()) {
                throw new BusinessLogicException("Draft/periode SHU tahun {$simulation['year']} sudah ada.");
            }

            $distribution = $this->shuRepository->createDraft([
                'year' => $simulation['year'],
                'total_shu' => $simulation['shu_pool'],
                'reserve_amount' => $simulation['reserve_amount'],
                'distributed_amount' => $simulation['total_distributed'],
                'recipient_count' => $simulation['recipient_count'],
                'handled_by' => $authorizer->id,
            ]);

            $this->shuRepository->saveMemberRows($distribution->id, $simulation['members']);

            return $distribution;
        });
    }

    public function getPeriods(): \Illuminate\Support\Collection
    {
        return $this->shuRepository->all();
    }

    /**
     * Riwayat SHU seorang anggota dengan rincian jasa modal vs partisipasi.
     */
    public function getMemberHistory(int $memberId): array
    {
        return ShuMember::with('distribution:id,year,status,distribution_date,distributed_amount')
            ->where('member_id', $memberId)
            ->orderByDesc('shu_distribution_id')
            ->get()
            ->map(fn (ShuMember $row) => [
                'year' => $row->distribution?->year,
                'status' => $row->distribution?->status,
                'distribution_date' => $row->distribution?->distribution_date?->toDateString(),
                'jasa_modal' => round((float) $row->simpanan_pokok_amount + (float) $row->simpanan_wajib_amount, 2),
                'jasa_partisipasi' => (float) $row->participation_amount,
                'total_shu' => (float) $row->total_shu,
                'paid_at' => $row->paid_at?->toIso8601String(),
            ])->all();
    }
}
