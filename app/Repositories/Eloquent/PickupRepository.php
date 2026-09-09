<?php

namespace App\Repositories\Eloquent;

use App\Models\Pickup;
use App\Models\PickupItem;
use App\Models\Transaction;
use App\Repositories\Contracts\PickupRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PickupRepository implements PickupRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Pickup::query()
            ->with('member:id,member_code,name,address,phone', 'officer:id,name')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['member_id'] ?? null, fn ($query, $memberId) => $query->where('member_id', $memberId))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('scheduled_at', $date))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('member', fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%"));
            })
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findByIdWithDetails(int $id): Pickup
    {
        return Pickup::with(
            'member.user:id,username',
            'officer:id,name',
            'items.category:id,name,type,unit,price_sorted,price_unsorted',
            'items.transaction:id,transaction_code',
        )->findOrFail($id);
    }

    public function createHeader(array $data): Pickup
    {
        return Pickup::create($data);
    }

    public function addItemsAndComplete(Pickup $pickup, array $itemRows, Transaction $feeTransaction, float $totalGross, float $totalFee, float $totalNet): void
    {
        foreach ($itemRows as $row) {
            PickupItem::create([
                ...$row,
                'pickup_id' => $pickup->id,
                'transaction_id' => $feeTransaction->id,
                'deposit_date' => now(),
            ]);
        }

        $pickup->update([
            'status' => 'selesai',
            'completed_at' => now(),
            'total_gross' => $totalGross,
            'total_fee' => $totalFee,
            'total_net' => $totalNet,
        ]);
    }

    public function cancel(Pickup $pickup, string $reason): void
    {
        $pickup->update([
            'status' => 'batal',
            'notes' => trim(($pickup->notes ? $pickup->notes."\n" : '')."[BATAL] {$reason}"),
        ]);
    }
}
