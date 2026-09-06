<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    /**
     * Jurnal umum kas (filter type / member / rentang tanggal).
     */
    public function paginate(array $filters): LengthAwarePaginator;

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
