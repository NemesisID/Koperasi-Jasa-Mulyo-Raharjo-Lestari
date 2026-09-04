<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->enum('location_type', ['gudang', 'jemput_rumah'])->default('gudang')->after('member_id');
            $table->decimal('total_gross', 15, 2)->default(0)->after('completed_at');
            $table->decimal('total_fee', 15, 2)->default(0)->after('total_gross');
            $table->decimal('total_net', 15, 2)->default(0)->after('total_fee');
        });

        Schema::table('pickup_items', function (Blueprint $table) {
            $table->unsignedInteger('unit_count')->default(0)->after('weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropColumn(['location_type', 'total_gross', 'total_fee', 'total_net']);
        });

        Schema::table('pickup_items', function (Blueprint $table) {
            $table->dropColumn('unit_count');
        });
    }
};
