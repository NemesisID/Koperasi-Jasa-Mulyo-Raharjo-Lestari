<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_id')->constrained('pickups')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->enum('issue_type', ['berat_salah', 'kategori_salah', 'harga_salah', 'lainnya']);
            $table->text('description');
            $table->string('proof_image')->nullable();
            $table->enum('status', ['diajukan', 'proses', 'diterima', 'ditolak'])->default('diajukan');
            $table->decimal('adjustment_amount', 15, 2)->default(0);
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
