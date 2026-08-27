<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shu_distributions', function (Blueprint $table) {
            $table->id();
            $table->year('year')->unique();
            $table->decimal('total_shu', 15, 2);
            $table->decimal('reserve_amount', 15, 2);
            $table->decimal('distributed_amount', 15, 2);
            $table->unsignedInteger('recipient_count');
            $table->enum('status', ['draft', 'dibagikan'])->default('draft');
            $table->date('distribution_date')->nullable();
            $table->foreignId('handled_by')->constrained('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shu_distributions');
    }
};
