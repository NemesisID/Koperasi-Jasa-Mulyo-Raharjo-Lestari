<?php

namespace App\Repositories\Eloquent;

use App\Models\FinanceCategory;
use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Str;

class TransactionRepository implements TransactionRepositoryInterface
{
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
