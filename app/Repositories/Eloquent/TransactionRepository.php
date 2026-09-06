<?php

namespace App\Repositories\Eloquent;

use App\Models\FinanceCategory;
use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Transaction::query()
            ->with('member:id,member_code,name', 'category:id,name,type,group_type', 'officer:id,name')
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['member_id'] ?? null, fn ($query, $memberId) => $query->where('member_id', $memberId))
            ->when($filters['start_date'] ?? null, fn ($query, $start) => $query->whereDate('transaction_date', '>=', $start))
            ->when($filters['end_date'] ?? null, fn ($query, $end) => $query->whereDate('transaction_date', '<=', $end))
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Transaction
    {
        return Transaction::create([
            ...$data,
            'transaction_code' => $data['transaction_code']
                ?? 'TRX-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4)),
            'transaction_date' => $data['transaction_date'] ?? now(),
        ]);
    }

    public function findById(int $id): Transaction
    {
        return Transaction::findOrFail($id);
    }

    public function updateStatus(int $id, string $status): Transaction
    {
        $transaction = $this->findById($id);
        $transaction->update(['status' => $status]);

        return $transaction->fresh();
    }

    public function findCategoryIdByName(string $name): ?int
    {
        return FinanceCategory::where('name', $name)->value('id');
    }
}
