<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trash_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['logam', 'besi', 'kertas', 'plastik', 'elektronik', 'organik', 'campur', 'lainnya'])->default('lainnya');
            $table->enum('unit', ['kg', 'biji', 'unit'])->default('kg');
            $table->decimal('price_sorted', 10, 2)->default(0);
            $table->decimal('price_unsorted', 10, 2)->default(0);
            // Harga jual ke pengepul/marketplace; harga bersih anggota = price_sell - price_admin.
            $table->decimal('price_sell', 10, 2)->default(0);
            // Biaya admin per unit — input manual (guideline 20% dari harga jual).
            $table->decimal('price_admin', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trash_categories');
    }
};
