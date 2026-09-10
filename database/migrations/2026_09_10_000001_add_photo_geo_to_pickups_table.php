<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// R3 (revisi fase-2 poin 4): dokumentasi foto timbang/pengambilan + geo-tag.
// Timestamp tidak perlu kolom baru — completed_at terisi saat weigh-items.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            // Tanpa after() — kolom "source" tidak ada di schema, after() hanya
            // diabaikan sqlite dan akan error di mysql.
            $table->string('photo_path', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'latitude', 'longitude']);
        });
    }
};
