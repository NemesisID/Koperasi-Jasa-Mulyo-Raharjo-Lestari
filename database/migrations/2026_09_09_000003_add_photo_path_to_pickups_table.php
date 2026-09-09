<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            // Foto dokumentasi pengambilan sampah — diinput petugas saat jemput.
            $table->string('photo_path', 255)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
