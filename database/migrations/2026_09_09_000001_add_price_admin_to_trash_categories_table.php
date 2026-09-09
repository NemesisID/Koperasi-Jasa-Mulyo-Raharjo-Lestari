<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trash_categories', function (Blueprint $table) {
            // Biaya admin per unit — input manual (guideline 20% dari harga jual).
            $table->decimal('price_admin', 10, 2)->default(0)->after('price_sell');
        });

        Schema::table('price_change_logs', function (Blueprint $table) {
            $table->decimal('old_price_admin', 10, 2)->nullable()->after('new_price_sell');
            $table->decimal('new_price_admin', 10, 2)->nullable()->after('old_price_admin');
        });
    }

    public function down(): void
    {
        Schema::table('trash_categories', function (Blueprint $table) {
            $table->dropColumn('price_admin');
        });

        Schema::table('price_change_logs', function (Blueprint $table) {
            $table->dropColumn(['old_price_admin', 'new_price_admin']);
        });
    }
};
