<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sprint 30 Sep poin 2 — hapus user gagal karena 4 FK `restrict` (transactions.handled_by,
// reports.created_by, shu_distributions.handled_by, setoran_koperasi.user_id) menahan
// baris berriwayat. Solusinya arsip (soft delete), bukan hapus permanen — sama dengan
// pola `members` (2026_09_17_000002).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
