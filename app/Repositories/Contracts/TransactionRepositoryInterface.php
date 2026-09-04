<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;

interface TransactionRepositoryInterface
{
    /**
     * Catat transaksi kas baru (transaction_code di-generate otomatis jika tidak ada).
     */
    public function create(array $data): Transaction;

    /**
     * Ambil satu transaksi berdasarkan id.
     */
    public function findById(int $id): Transaction;

    /**
     * Ubah status transaksi (dipakai rollback pembatalan).
     */
    public function updateStatus(int $id, string $status): Transaction;

    /**
     * Cari id kategori keuangan berdasarkan nama (mis. "Potongan Admin Sampah 20%").
     */
    public function findCategoryIdByName(string $name): ?int;
}
