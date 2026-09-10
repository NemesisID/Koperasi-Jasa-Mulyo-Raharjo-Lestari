<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Snapshot schema "kondisi final" — berjalan PALING AKHIR dengan guard hasTable,
// jadi tidak bentrok dengan migrasi individual 000001–000023 yang membuat tabel
// yang sama. (ponytail: guard, bukan 23 file individual yang dihapus — aman
// untuk DB live yang mencatat salah satu set saja.)
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['pengurus', 'petugas', 'anggota']);
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->bigInteger('expiration')->index();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedSmallInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('connection');
            $table->string('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();

            $table->index(['connection', 'queue', 'failed_at']);
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('member_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
        });

        Schema::create('trash_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['logam', 'besi', 'kertas', 'plastik', 'elektronik', 'organik', 'campur', 'lainnya'])->default('lainnya');
            $table->enum('unit', ['kg', 'biji', 'unit'])->default('kg');
            $table->decimal('price_sorted', 10, 2)->default(0);
            $table->decimal('price_unsorted', 10, 2)->default(0);
            // Harga jual ke pengepul/marketplace; harga anggota (80%) dihitung on-the-fly.
            $table->decimal('price_sell', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['income', 'expense']);
            $table->enum('group_type', ['simpanan_pokok', 'simpanan_wajib', 'tipping_fee', 'operasional', 'lainnya', 'penjualan_sampah']);
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('member_category_id')->constrained('member_categories')->onDelete('restrict');
            $table->string('member_code', 50)->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->enum('status', ['aktif', 'nonaktif', 'suspend'])->default('aktif');
            $table->date('join_date');
            $table->timestamps();
        });

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

        Schema::create('detail_pengangkutan', function (Blueprint $table) {
            $table->string('id', 36)->primary(); // Generate from Backend!
            $table->foreignId('user_id');
            $table->dateTime('jadwal_angkut');
            $table->decimal('total_organik', 8, 2)->default(0);
            $table->decimal('total_anorganik', 8, 2)->default(0);
            $table->string('kecamatan', 100)->nullable();
            $table->string('desa', 100)->nullable();
            $table->string('dusun', 100)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('rt', 5)->nullable();
            $table->text('alamat');
            $table->timestamps();

            $table->foreign('user_id', 'fk_pengangkutan_user')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->index('user_id', 'idx_pengangkutan_user_id');
        });

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
            $table->timestamps();
        });

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

        Schema::create('price_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trash_category_id')->constrained('trash_categories')->cascadeOnDelete();
            $table->decimal('old_price_sorted', 15, 2);
            $table->decimal('new_price_sorted', 15, 2);
            $table->decimal('old_price_unsorted', 15, 2);
            $table->decimal('new_price_unsorted', 15, 2);
            $table->decimal('old_price_sell', 10, 2)->nullable();
            $table->decimal('new_price_sell', 10, 2)->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('withdraw_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->enum('method', ['tunai', 'transfer']);
            $table->string('bank_name')->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('account_holder')->nullable();
            $table->enum('status', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->string('proof_file')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_requests');
        Schema::dropIfExists('price_change_logs');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('shu_members');
        Schema::dropIfExists('shu_distributions');
        Schema::dropIfExists('pickup_items');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('pickups');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('detail_pengangkutan');
        Schema::dropIfExists('setoran_koperasi');
        Schema::dropIfExists('members');
        Schema::dropIfExists('finance_categories');
        Schema::dropIfExists('trash_categories');
        Schema::dropIfExists('member_categories');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
