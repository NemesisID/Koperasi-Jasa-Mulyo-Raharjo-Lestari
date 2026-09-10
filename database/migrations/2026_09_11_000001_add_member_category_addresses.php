<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Alamat per kategori member (rumah/pasar) — dual-status boleh alamat berbeda.
// Kolom `address` lama tetap jadi fallback umum.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->text('address_rumah')->nullable()->after('address');
            $table->text('address_pasar')->nullable()->after('address_rumah');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['address_rumah', 'address_pasar']);
        });
    }
};
