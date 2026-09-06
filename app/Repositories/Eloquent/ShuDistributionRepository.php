<?php

namespace App\Repositories\Eloquent;

use App\Models\ShuDistribution;
use App\Models\ShuMember;
use App\Repositories\Contracts\ShuDistributionRepositoryInterface;
use Illuminate\Support\Collection;

class ShuDistributionRepository implements ShuDistributionRepositoryInterface
{
    public function all(): Collection
    {
        return ShuDistribution::with('handledBy:id,name')
            ->withCount('shuMembers')
            ->orderByDesc('year')
            ->get();
    }

    public function findById(int $id): ShuDistribution
    {
        return ShuDistribution::with('handledBy:id,name', 'shuMembers.member:id,member_code,name')->findOrFail($id);
    }

    public function createDraft(array $data): ShuDistribution
    {
        return ShuDistribution::create([
            ...$data,
            'status' => 'draft',
            'recipient_count' => 0,
        ]);
    }

    public function saveMemberRows(int $distributionId, array $rows): void
    {
        foreach ($rows as $row) {
            ShuMember::create([
                'shu_distribution_id' => $distributionId,
                'member_id' => $row['member_id'],
                'simpanan_pokok_amount' => $row['simpanan_pokok_amount'],
                'simpanan_wajib_amount' => $row['simpanan_wajib_amount'],
                'participation_amount' => $row['participation_shu'],
                'total_shu' => $row['total_shu'],
                'status' => 'menunggu',
            ]);
        }
    }
}
