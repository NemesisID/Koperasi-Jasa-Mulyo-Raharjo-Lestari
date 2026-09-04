<?php

namespace App\Repositories\Contracts;

use App\Models\Member;
use App\Models\MemberCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface MemberRepositoryInterface
{
    /**
     * Daftar anggota terpaginasi dengan filter (status, kategori, pencarian).
     */
    public function paginate(array $filters): LengthAwarePaginator;

    /**
     * Ambil satu anggota berdasarkan id.
     */
    public function findById(int $id): Member;

    /**
     * Buat record anggota baru.
     */
    public function create(array $data): Member;

    /**
     * Perbarui record anggota berdasarkan id.
     */
    public function update(int $id, array $data): Member;

    /**
     * Generate kode anggota unik dengan format MBR-YYYYMM-XXXX.
     */
    public function generateMemberCode(): string;

    /**
     * Ambil id kategori anggota default untuk registrasi mandiri.
     */
    public function getDefaultCategoryId(): ?int;

    /**
     * Daftar seluruh kategori anggota.
     */
    public function listCategories(): Collection;

    /**
     * Buat kategori anggota baru.
     */
    public function createCategory(array $data): MemberCategory;
}
