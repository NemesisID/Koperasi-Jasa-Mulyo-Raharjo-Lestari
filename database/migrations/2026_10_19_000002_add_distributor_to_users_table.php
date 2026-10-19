<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kita ubah role menjadi string agar fleksibel menampung distributor & customer
            $table->string('role', 50)->default('anggota')->change();
            $table->boolean('is_distributor_verified')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_distributor_verified');
        });
    }
};
