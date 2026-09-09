<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code', 100)->unique();
            $table->foreignId('member_id')->nullable()->constrained('members')->onDelete('set null');
            $table->foreignId('category_id')->constrained('finance_categories')->onDelete('restrict');
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->enum('payment_method', ['tunai', 'transfer', 'sampah']);
            $table->enum('status', ['pending', 'berhasil', 'gagal'])->default('pending');
            $table->timestamp('transaction_date');
            $table->foreignId('handled_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
