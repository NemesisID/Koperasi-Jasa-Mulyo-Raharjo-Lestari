<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\FinanceCategory;

return new class extends Migration
{
    public function up(): void
    {
        // Kategori jualan sampah (marketplace) + kategori pemasukan dari sampah untuk labeling cashflow.
        foreach ([
            ['name' => 'Penjualan Sampah', 'type' => 'income', 'group_type' => 'penjualan_sampah'],
            ['name' => 'Pemasukan Sampah Lainnya', 'type' => 'income', 'group_type' => 'penjualan_sampah'],
        ] as $cat) {
            FinanceCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }
    }

    public function down(): void
    {
        FinanceCategory::whereIn('name', ['Penjualan Sampah', 'Pemasukan Sampah Lainnya'])->delete();
    }
};
