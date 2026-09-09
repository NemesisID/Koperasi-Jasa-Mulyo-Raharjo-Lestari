<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('member_id')->constrained('members')->onDelete('restrict');
            $table->enum('location_type', ['gudang', 'jemput_rumah'])->default('gudang');
            $table->boolean('is_sorted')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('total_gross', 15, 2)->default(0);
            $table->decimal('total_fee', 15, 2)->default(0);
            $table->decimal('total_net', 15, 2)->default(0);
            $table->enum('status', ['menunggu', 'selesai', 'batal'])->default('menunggu');
            $table->text('notes')->nullable();
            // Sumber tiket: 'manual' (pesan ala gojek), 'rutin' (scheduler bulanan), 'auto' (trigger manual endpoint)
            $table->string('source', 20)->default('manual');
            // Foto dokumentasi pengambilan sampah — diinput petugas saat jemput.
            $table->string('photo_path', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickups');
    }
};
