<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['income', 'expense']);
            $table->enum('group_type', ['simpanan_pokok', 'simpanan_wajib', 'tipping_fee', 'operasional', 'lainnya', 'penjualan_sampah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_categories');
    }
};
