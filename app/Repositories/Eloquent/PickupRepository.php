<?php

namespace App\Repositories\Eloquent;

use App\Models\Pickup;
use App\Models\PickupItem;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\PickupRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PickupRepository implements PickupRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Pickup::query()
            ->with('member:id,member_code,name,address,address_rumah,address_pasar,phone', 'officer:id,name', 'receipt')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['member_id'] ?? null, fn ($query, $memberId) => $query->where('member_id', $memberId))
            ->when($filters['officer_id'] ?? null, fn ($query, $officerId) => $query->where('officer_id', $officerId))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('scheduled_at', $date))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('member', fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%"));
            })
            // Urutan penjemputan (R23): urutan petugas dulu. `sort_order IS NULL`
            // menaruh baris tanpa urutan ke bawah di MySQL maupun SQLite (ASC
            // default menaruh NULL di awal), lalu terbaru dulu.
            ->orderByRaw('sort_order IS NULL, sort_order')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function statusCounts(): array
    {
        // Satu query agregat untuk seluruh tabel — bukan hasil map atas sepotong halaman,
        // yang bikin dashboard pengurus dan petugas menampilkan angka berbeda.
        $counts = Pickup::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return [
            'menunggu' => (int) $counts->get('menunggu', 0),
            'selesai' => (int) $counts->get('selesai', 0),
            'batal' => (int) $counts->get('batal', 0),
            'total' => (int) $counts->sum(),
            'total_net_selesai' => round((float) Pickup::where('status', 'selesai')->sum('total_net'), 2),
        ];
    }

    public function findByIdWithDetails(int $id): Pickup
    {
        return Pickup::with(
            'member.user:id,username',
            'officer:id,name',
            'items.category:id,name,type,unit,price_sorted,price_unsorted',
            'items.transaction:id,transaction_code',
            'receipt',
        )->findOrFail($id);
    }

    public function createHeader(array $data): Pickup
    {
        // Tiket baru masuk ke ekor antrean penjemputan.
        $data['sort_order'] = (int) Pickup::max('sort_order') + 1;

        return Pickup::create($data);
    }

    public function addItemsAndComplete(Pickup $pickup, array $itemRows, Transaction $purchaseTransaction, float $totalGross, float $totalFee, float $totalNet, ?User $officer = null): void
    {
        foreach ($itemRows as $row) {
            PickupItem::create([
                ...$row,
                'pickup_id' => $pickup->id,
                // Item menunjuk jurnal beli sampah miliknya — dipakai rollback
                // saat timbang ulang/batal, jadi tidak perlu lookup by deskripsi.
                'transaction_id' => $purchaseTransaction->id,
                'deposit_date' => now(),
            ]);
        }

        $pickup->update([
            'status' => 'selesai',
            'completed_at' => now(),
            // Tiket buatan anggota (minta jemput) diambil alih petugas saat ditimbang.
            'officer_id' => $pickup->officer_id ?? $officer?->id,
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

    public function reorder(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $position => $id) {
                Pickup::where('id', $id)->update(['sort_order' => $position + 1]);
            }
        });
    }
}
