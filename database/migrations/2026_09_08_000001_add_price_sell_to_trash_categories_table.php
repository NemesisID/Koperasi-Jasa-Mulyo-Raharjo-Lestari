<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trash_categories', function (Blueprint $table) {
            // Harga jual ke pengepul/marketplace; harga anggota (80%) dihitung on-the-fly.
            $table->decimal('price_sell', 10, 2)->default(0)->after('price_unsorted');
        });

        Schema::table('price_change_logs', function (Blueprint $table) {
            $table->decimal('old_price_sell', 10, 2)->nullable()->after('new_price_unsorted');
            $table->decimal('new_price_sell', 10, 2)->nullable()->after('old_price_sell');
        });
    }

    public function down(): void
    {
        Schema::table('trash_categories', function (Blueprint $table) {
            $table->dropColumn('price_sell');
        });

        Schema::table('price_change_logs', function (Blueprint $table) {
            $table->dropColumn(['old_price_sell', 'new_price_sell']);
        });
    }
};
