<?php

namespace App\Repositories\Eloquent;

use App\Models\Complaint;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ComplaintRepository implements ComplaintRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Complaint::query()
            ->with('member:id,member_code,name', 'pickup:id,member_id,total_net,status', 'resolver:id,name')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['member_id'] ?? null, fn ($query, $memberId) => $query->where('member_id', $memberId))
            ->when($filters['pickup_id'] ?? null, fn ($query, $pickupId) => $query->where('pickup_id', $pickupId))
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(int $id): Complaint
    {
        return Complaint::with(
            'member.user:id,username',
            'pickup:id,member_id,total_gross,total_fee,total_net,status,completed_at',
            'resolver:id,name',
        )->findOrFail($id);
    }

    public function create(array $data): Complaint
    {
        return Complaint::create($data);
    }

    public function update(int $id, array $data): Complaint
    {
        $complaint = Complaint::findOrFail($id);
        $complaint->update($data);

        return $this->findById($id);
    }
}
