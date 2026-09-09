<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_id')->nullable()->constrained('pickups')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('trash_categories')->onDelete('restrict');
            $table->decimal('weight_kg', 8, 2);
            $table->unsignedInteger('unit_count')->default(0);
            $table->decimal('total_value', 15, 2);
            $table->timestamp('deposit_date')->useCurrent();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_items');
    }
};
