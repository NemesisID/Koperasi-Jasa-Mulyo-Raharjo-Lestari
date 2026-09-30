<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sprint 30 Sep poin 3 — belum ada fitur hapus item sampah sama sekali. FK
// `pickup_items.category_id` memakai `restrict`, jadi item yang pernah ditimbang
// tidak bisa dihapus permanen. Solusinya arsip (soft delete): katalog aktif
// menyusut, riwayat timbangan tetap utuh.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trash_categories', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('trash_categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
