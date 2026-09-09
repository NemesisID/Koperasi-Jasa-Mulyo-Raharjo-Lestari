<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['saldo', 'laba_rugi', 'simpanan', 'operasional']);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('file_path');
            $table->enum('status', ['review', 'finalized'])->default('review');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
