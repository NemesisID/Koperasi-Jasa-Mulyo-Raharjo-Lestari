<?php

namespace App\Repositories\Contracts;

use App\Models\Pickup;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PickupRepositoryInterface
{
    /**
     * Daftar pickup terpaginasi dengan filter (status, tanggal).
     */
    public function paginate(array $filters): LengthAwarePaginator;

    /**
     * Hitungan pickup per status dari SELURUH tabel (bukan sepotong halaman),
     * supaya dashboard pengurus & petugas menampilkan angka yang sama.
     *
     * @return array{menunggu: int, selesai: int, batal: int, total: int, total_net_selesai: float}
     */
    public function statusCounts(): array;

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
     * Officer mengisi petugas penimbang untuk tiket buatan anggota (minta jemput).
     * $purchaseTransaction = jurnal beli sampah; id-nya disimpan di tiap item untuk rollback.
     */
    public function addItemsAndComplete(Pickup $pickup, array $itemRows, Transaction $purchaseTransaction, float $totalGross, float $totalFee, float $totalNet, ?User $officer = null): void;

    /**
     * Batalkan pickup (status batal, catat alasan).
     */
    public function cancel(Pickup $pickup, string $reason): void;
}
