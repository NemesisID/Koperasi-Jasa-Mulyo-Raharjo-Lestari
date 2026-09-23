<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah kolom price_per_unit ke pickup_items agar harga per unit saat
     * transaksi tersimpan eksplisit (frozen) — tidak berubah walau admin
     * mengganti harga katalog sampah di kemudian hari.
     */
    public function up(): void
    {
        Schema::table('pickup_items', function (Blueprint $table) {
            $table->decimal('price_per_unit', 15, 2)->default(0)->after('unit_count');
        });

        // Backfill data lama: hitung price_per_unit dari total_value / quantity
        // yang sudah frozen di DB. COALESCE + NULLIF menghindari division by zero.
        DB::statement("
            UPDATE pickup_items
            SET price_per_unit = ROUND(
                total_value / NULLIF(
                    CASE WHEN weight_kg > 0 THEN weight_kg ELSE unit_count END,
                    0
                ),
                2
            )
            WHERE price_per_unit = 0
              AND total_value > 0
        ");
    }

    public function down(): void
    {
        Schema::table('pickup_items', function (Blueprint $table) {
            $table->dropColumn('price_per_unit');
        });
    }
};
