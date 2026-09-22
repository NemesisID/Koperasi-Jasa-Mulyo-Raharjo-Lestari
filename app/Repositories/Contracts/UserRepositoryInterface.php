<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * Cari user berdasarkan email ATAU username (untuk login identity).
     */
    public function findByEmailOrUsername(string $identity): ?User;

    /**
     * Daftar user terpaginasi dengan filter (role, pencarian nama/username/email).
     */
    public function paginate(array $filters): LengthAwarePaginator;

    /**
     * Ambil satu user berdasarkan id.
     */
    public function findById(int $id): User;

    /**
     * Buat record user baru.
     */
    public function create(array $data): User;

    /**
     * Perbarui record user berdasarkan id.
     */
    public function update(int $id, array $data): User;

    /**
     * Ambil user beserta relasi member (profil anggota).
     */
    public function findWithMember(int $id): User;

    /**
     * Hapus record user.
     */
    public function delete(int $id): void;
}
