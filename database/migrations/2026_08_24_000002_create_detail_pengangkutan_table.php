<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('detail_pengangkutan', function (Blueprint $table) {
            $table->string('id', 36)->primary(); // Generate from Backend!
            $table->string('user_id', 36);
            $table->dateTime('jadwal_angkut');
            $table->decimal('total_organik', 8, 2)->default(0);
            $table->decimal('total_anorganik', 8, 2)->default(0);
            $table->string('kecamatan', 100)->nullable();
            $table->string('desa', 100)->nullable();
            $table->string('dusun', 100)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('rt', 5)->nullable();
            $table->timestamps();
            $table->text('alamat');

            $table->foreign('user_id', 'fk_pengangkutan_user')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->index('user_id', 'idx_pengangkutan_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_pengangkutan');
    }
};
