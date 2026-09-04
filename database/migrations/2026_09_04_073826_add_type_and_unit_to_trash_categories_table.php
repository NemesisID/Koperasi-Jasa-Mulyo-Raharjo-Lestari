<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trash_categories', function (Blueprint $table) {
            $table->enum('type', ['logam', 'besi', 'kertas', 'plastik', 'elektronik', 'organik', 'campur', 'lainnya'])->default('lainnya')->after('name');
            $table->enum('unit', ['kg', 'biji', 'unit'])->default('kg')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('trash_categories', function (Blueprint $table) {
            $table->dropColumn(['type', 'unit']);
        });
    }
};
