<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setoran_koperasi', function (Blueprint $table) {
            // Asal dana setoran: 'tunai' = dibayar di koperasi, 'saldo' = dipotong
            // otomatis dari saldo dompet anggota (hold bulanan #15). Saldo dompet
            // dihitung turunan, jadi baris inilah yang jadi bukti pemotongannya.
            $table->enum('sumber', ['tunai', 'saldo'])->default('tunai')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('setoran_koperasi', function (Blueprint $table) {
            $table->dropColumn('sumber');
        });
    }
};
