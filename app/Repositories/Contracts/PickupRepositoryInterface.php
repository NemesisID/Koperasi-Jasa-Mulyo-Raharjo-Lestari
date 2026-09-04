<?php

namespace App\Repositories\Contracts;

use App\Models\Pickup;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PickupRepositoryInterface
{
    /**
     * Daftar pickup terpaginasi dengan filter (status, tanggal).
     */
    public function paginate(array $filters): LengthAwarePaginator;

    /**
     * Satu pickup beserta relasi member, petugas, dan rincian item.
     */
    public function findByIdWithDetails(int $id): Pickup;

    /**
     * Buat tiket pickup baru (status menunggu).
     */
    public function createHeader(array $data): Pickup;

    /**
     * Simpan rincian item, kunci nilai total, dan tandai selesai — dipanggil dalam DB::transaction service.
     */
    public function addItemsAndComplete(Pickup $pickup, array $itemRows, Transaction $feeTransaction, float $totalGross, float $totalFee, float $totalNet): void;

    /**
     * Batalkan pickup (status batal, catat alasan).
     */
    public function cancel(Pickup $pickup, string $reason): void;
}
