<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            // Urutan penjemputan global (R23) — petugas mengerjakan dari urutan kecil.
            $table->unsignedInteger('sort_order')->nullable()->after('id');
            $table->index('sort_order');
        });

        // Backfill data lama mengikuti urutan created_at; tiket baru di-append lewat
        // PickupRepository::createHeader (sort_order = max + 1).
        $ids = DB::table('pickups')->orderBy('created_at')->orderBy('id')->pluck('id');
        foreach ($ids as $index => $id) {
            DB::table('pickups')->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
