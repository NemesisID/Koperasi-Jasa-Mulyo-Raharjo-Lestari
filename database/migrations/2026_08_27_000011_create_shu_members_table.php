<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shu_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shu_distribution_id')->constrained('shu_distributions')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members')->onDelete('restrict');
            $table->decimal('simpanan_pokok_amount', 15, 2)->default(0);
            $table->decimal('simpanan_wajib_amount', 15, 2)->default(0);
            $table->decimal('participation_amount', 15, 2)->default(0);
            $table->decimal('total_shu', 15, 2)->default(0);
            $table->enum('status', ['menunggu', 'sudah_dibagikan'])->default('menunggu');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shu_members');
    }
};
