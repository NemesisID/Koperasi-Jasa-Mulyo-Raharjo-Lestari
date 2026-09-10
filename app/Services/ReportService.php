<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Pickup;
use App\Models\PickupItem;
use App\Models\Transaction;
use App\Models\WithdrawRequest;

class ReportService
{
    /**
     * Widget statistik dashboard: total kas, tonase bulan ini, anggota aktif.
     */
    public function getDashboardStats(): array
    {
        $totalIncome = (float) Transaction::where('type', 'income')->where('status', 'berhasil')->sum('amount');
        $totalExpense = (float) Transaction::where('type', 'expense')->where('status', 'berhasil')->sum('amount');

        // Saldo mengendap: penarikan pending yang belum dieksekusi
        $heldBalance = (float) WithdrawRequest::where('status', 'pending')->sum('amount');

        return [
            'total_kas' => round($totalIncome - $totalExpense, 2),
            'saldo_mengendap' => $heldBalance,
            'tonase_sampah_bulan_ini' => round(
                (float) PickupItem::join('pickups', 'pickups.id', '=', 'pickup_items.pickup_id')
                    ->where('pickups.status', 'selesai')
                    ->whereMonth('pickups.completed_at', now()->month)
                    ->whereYear('pickups.completed_at', now()->year)
                    ->sum('pickup_items.weight_kg'),
                2,
            ),
            'jumlah_anggota_aktif' => Member::where('status', 'aktif')->count(),
        ];
    }

    /**
     * Laporan keuangan periode: laba rugi ringkas dari jurnal kas.
     */
    public function getFinancialReport(string $start, string $end): array
    {
        $base = Transaction::where('status', 'berhasil')
            ->whereDate('transaction_date', '>=', $start)
            ->whereDate('transaction_date', '<=', $end);

        $income = (clone $base)->where('type', 'income')
            ->selectRaw('category_id, SUM(amount) as total')
            ->with('category:id,name')
            ->groupBy('category_id')
            ->get()
            ->map(fn ($r) => ['category' => $r->category?->name, 'total' => (float) $r->total]);

        $expense = (clone $base)->where('type', 'expense')
            ->selectRaw('category_id, SUM(amount) as total')
            ->with('category:id,name')
            ->groupBy('category_id')
            ->get()
            ->map(fn ($r) => ['category' => $r->category?->name, 'total' => (float) $r->total]);

        $totalIncome = round($income->sum('total'), 2);
        $totalExpense = round($expense->sum('total'), 2);

        return [
            'period' => ['start' => $start, 'end' => $end],
            'income' => $income->all(),
            'expense' => $expense->all(),
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_profit' => round($totalIncome - $totalExpense, 2),
        ];
    }

    /**
     * Rekapitulasi tonase per kategori sampah & per wilayah.
     */
    public function getTrashVolumeReport(array $filters): array
    {
        $query = PickupItem::join('pickups', 'pickups.id', '=', 'pickup_items.pickup_id')
            ->join('trash_categories', 'trash_categories.id', '=', 'pickup_items.category_id')
            ->where('pickups.status', 'selesai')
            ->when($filters['start_date'] ?? null, fn ($q, $start) => $q->whereDate('pickups.completed_at', '>=', $start))
            ->when($filters['end_date'] ?? null, fn ($q, $end) => $q->whereDate('pickups.completed_at', '<=', $end));

        $perCategory = (clone $query)
            ->selectRaw('trash_categories.type, SUM(pickup_items.weight_kg) as total_kg, COUNT(DISTINCT pickups.member_id) as contributors')
            ->groupBy('trash_categories.type')
            ->get()
            ->map(fn ($r) => ['type' => $r->type, 'total_kg' => round((float) $r->total_kg, 2), 'contributors' => (int) $r->contributors]);

        // Tonase per wilayah: dari alamat ritase logistik
        $perWilayah = \App\Models\DetailPengangkutan::query()
            ->selectRaw('desa, rw, SUM(total_organik + total_anorganik) as total_kg')
            ->when($filters['start_date'] ?? null, fn ($q, $start) => $q->whereDate('jadwal_angkut', '>=', $start))
            ->when($filters['end_date'] ?? null, fn ($q, $end) => $q->whereDate('jadwal_angkut', '<=', $end))
            ->groupBy('desa', 'rw')
            ->get()
            ->map(fn ($r) => ['desa' => $r->desa, 'rw' => $r->rw, 'total_kg' => round((float) $r->total_kg, 2)]);

        // Pendapatan per kategori sampah (rupiah, dari nota timbang)
        $perItemCategory = (clone $query)
            ->selectRaw('trash_categories.name, trash_categories.type, SUM(pickup_items.weight_kg) as total_kg, SUM(pickup_items.total_value) as total_value')
            ->groupBy('trash_categories.id', 'trash_categories.name', 'trash_categories.type')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'type' => $r->type,
                'total_kg' => round((float) $r->total_kg, 2),
                'total_value' => (float) $r->total_value,
            ]);

        return [
            'period' => [
                'start' => $filters['start_date'] ?? null,
                'end' => $filters['end_date'] ?? null,
            ],
            'per_category' => $perCategory->all(),
            'per_item_category' => $perItemCategory->all(),
            'per_wilayah' => $perWilayah->all(),
            'total_kg' => round($perCategory->sum('total_kg'), 2),
        ];
    }

    /**
     * Baris export (flatten) untuk laporan on-the-flight — tanpa perlu baris
     * Report finalized di DB.
     *
     * @return array{filename: string, rows: array<int, array<int, mixed>>}
     */
    public function getExportRows(string $type, string $start, string $end): array
    {
        $data = match ($type) {
            'laba_rugi' => $this->getFinancialReport($start, $end),
            default => $this->getTrashVolumeReport(['start_date' => $start, 'end_date' => $end]),
        };

        return [
            'filename' => 'laporan-'.$type.'-'.now()->format('Ymd'),
            'rows' => iterator_to_array($this->flatten($data)),
        ];
    }

    /**
     * Flatten array laporan multi-level jadi baris CSV/XLSX/PDF.
     *
     * @return \Generator<int, array<int, mixed>>
     */
    private function flatten(array $data): \Generator
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Daftar baris (income/expense/per_category): kolom = array keys baris pertama
                if (isset($value[0]) && is_array($value[0])) {
                    yield [ucwords(str_replace('_', ' ', (string) $key))];
                    yield array_keys($value[0]);

                    foreach ($value as $row) {
                        yield array_values($row);
                    }
                    yield [];
                } elseif (array_is_list($value) === false && ! empty($value)) {
                    // Map key-value (mis. period): pecah jadi baris
                    yield [ucwords(str_replace('_', ' ', (string) $key))];
                    yield array_keys($value);
                    yield array_values($value);
                    yield [];
                } else {
                    yield [$key, implode(', ', array_map('strval', $value))];
                }
            } else {
                yield [$key, $value];
            }
        }
    }
}
