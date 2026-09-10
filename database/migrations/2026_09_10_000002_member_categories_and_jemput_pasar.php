<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Revisi fase-3 poin 3: kategori anggota (rumah/pasar, boleh keduanya) + lokasi jemput pasar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->json('categories')->nullable()->after('member_category_id');
        });

        Schema::table('pickups', function (Blueprint $table) {
            $table->enum('location_type', ['gudang', 'jemput_rumah', 'jemput_pasar'])->default('gudang')->change();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('categories');
        });

        Schema::table('pickups', function (Blueprint $table) {
            $table->enum('location_type', ['gudang', 'jemput_rumah'])->default('gudang')->change();
        });
    }
};
