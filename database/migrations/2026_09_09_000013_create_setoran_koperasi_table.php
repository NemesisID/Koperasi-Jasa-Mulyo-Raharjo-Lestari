<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setoran_koperasi', function (Blueprint $table) {
            $table->string('id', 36)->primary(); // Generate from Backend!
            $table->foreignId('user_id');
            $table->enum('jenis', ['PEMASUKAN', 'PENGELUARAN']);
            $table->decimal('jumlah', 12, 2);
            $table->enum('status', ['PENDING', 'PROSES', 'SELESAI', 'BATAL'])->default('PENDING');
            $table->enum('label', ['SHU', 'POKOK', 'WAJIB', 'TIPPING', 'SUKARELA']);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'fk_setoran_user')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->index('user_id', 'idx_setoran_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setoran_koperasi');
    }
};
