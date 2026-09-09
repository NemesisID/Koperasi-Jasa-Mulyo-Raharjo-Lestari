<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            // Sumber tiket: 'manual' (pesan ala gojek), 'rutin' (scheduler bulanan), 'auto' (trigger manual endpoint)
            $table->string('source', 20)->default('manual')->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
