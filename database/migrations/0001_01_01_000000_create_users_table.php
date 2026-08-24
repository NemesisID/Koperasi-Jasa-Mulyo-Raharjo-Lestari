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
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 36)->primary(); // Generate from Backend (UUID)
            $table->string('nama', 150);
            $table->enum('role', ['WARGA', 'PENGURUS', 'PENGEPUL'])->default('WARGA');
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->string('no_wa', 20)->unique();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('dusun', 100)->nullable();
            $table->string('desa', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('badge', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('email', 'idx_users_email');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id', 36)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
