<?php

namespace App\Repositories\Contracts;

use App\Models\TrashCategory;
use Illuminate\Support\Collection;

interface TrashCategoryRepositoryInterface
{
    /**
     * Daftar katalog sampah aktif dengan filter opsional (type, is_active).
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Data papan harga: kategori aktif + log perubahan harga terakhir.
     */
    public function getPriceBoardData(): Collection;

    /**
     * Ambil satu kategori sampah.
     */
    public function findById(int $id): TrashCategory;

    /**
     * Buat kategori sampah baru.
     */
    public function create(array $data): TrashCategory;

    /**
     * Perbarui data kategori (non-harga).
     */
    public function update(int $id, array $data): TrashCategory;

    /**
     * Update harga + catat audit log dalam satu transaksi.
     */
    public function updatePriceWithAudit(int $id, array $priceData, int $userId): TrashCategory;

    /**
     * Riwayat audit perubahan harga sebuah kategori.
     */
    public function priceHistory(int $id): Collection;
}
