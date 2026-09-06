<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    /**
     * Jurnal umum kas dengan filter type/member/rentang tanggal.
     */
    public function getTransactions(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->transactionRepository->paginate($filters);
    }

    public function createTransaction(array $data, User $handler): Transaction
    {
        return $this->transactionRepository->create([
            ...$data,
            'status' => 'berhasil',
            'handled_by' => $handler->id,
        ]);
    }
}
