<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ploting petugas disimpan per ANGGOTA (per alamat), bukan per user.
     * Satu user dengan 2 member (rumah + pasar) = 2 baris ploting, tiap baris
     * bisa punya petugas berbeda.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('officer_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
        });

        // Migrasi data lama: anggota yang sudah ada diplot ke petugas tertua (petugas1).
        // Urut created_at lalu id supaya deterministik saat created_at sama.
        $petugas1 = DB::table('users')
            ->where('role', 'petugas')
            ->orderBy('created_at')
            ->orderBy('id')
            ->value('id');

        if ($petugas1 !== null) {
            DB::table('members')->whereNull('officer_id')->update(['officer_id' => $petugas1]);
        }
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('officer_id');
        });
    }
};
