<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ponytail: DB::statement enum — enum kolom role tak bisa diubah dengan Change()
        Schema::getConnection()->statement(
            "ALTER TABLE users MODIFY role ENUM('ketua','pengurus','petugas','pengepul','anggota') NOT NULL"
        );
    }

    public function down(): void
    {
        Schema::getConnection()->statement(
            "ALTER TABLE users MODIFY role ENUM('ketua','pengurus','petugas','anggota') NOT NULL"
        );
    }
};
