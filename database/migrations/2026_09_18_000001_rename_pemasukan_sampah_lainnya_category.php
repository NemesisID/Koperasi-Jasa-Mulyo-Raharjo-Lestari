<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// R17: label kategori keuangan disesuaikan ke istilah bisnis —
// "Pemasukan Sampah Lainnya" jadi "Penjualan Produk Lain".
// Seeder saja tidak cukup: DB live sudah terisi dan tidak di-reseed.
return new class extends Migration
{
    private const OLD = 'Pemasukan Sampah Lainnya';

    private const NEW = 'Penjualan Produk Lain';

    public function up(): void
    {
        // Guard: kalau nama baru sudah ada (DB pernah di-seed versi baru),
        // update akan menyisakan dua baris kategori bermakna sama. Tidak ada
        // unique constraint di kolom name, jadi dicek manual.
        if (DB::table('finance_categories')->where('name', self::NEW)->exists()) {
            return;
        }

        DB::table('finance_categories')->where('name', self::OLD)->update(['name' => self::NEW]);
    }

    public function down(): void
    {
        DB::table('finance_categories')->where('name', self::NEW)->update(['name' => self::OLD]);
    }
};
