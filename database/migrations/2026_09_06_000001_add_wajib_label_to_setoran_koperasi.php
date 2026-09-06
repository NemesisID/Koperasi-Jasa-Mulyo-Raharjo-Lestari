<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setoran_koperasi', function (Blueprint $table) {
            $table->enum('label', ['SHU', 'POKOK', 'WAJIB', 'TIPPING', 'SUKARELA'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('setoran_koperasi', function (Blueprint $table) {
            $table->enum('label', ['SHU', 'POKOK', 'TIPPING', 'SUKARELA'])->change();
        });
    }
};
