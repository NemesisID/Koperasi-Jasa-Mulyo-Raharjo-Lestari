<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Revisi: role cukup 3 — pengurus (menyerap ketua), petugas, anggota.
// Baris lama role=ketua di-upgrade ke pengurus sebelum enum dipersempit.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'ketua')->update(['role' => 'pengurus']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['pengurus', 'petugas', 'anggota'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['ketua', 'pengurus', 'petugas', 'anggota'])->change();
        });
    }
};
