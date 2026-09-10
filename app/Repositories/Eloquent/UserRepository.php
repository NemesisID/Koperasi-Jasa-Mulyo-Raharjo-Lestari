<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function findByEmailOrUsername(string $identity): ?User
    {
        return User::where('email', $identity)
            ->orWhere('username', $identity)
            ->first();
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->with('member')
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(int $id): User
    {
        return User::findOrFail($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);

        return $user->fresh();
    }

    public function findWithMember(int $id): User
    {
        return User::with('member.category')->findOrFail($id);
    }

    public function delete(int $id): void
    {
        // ponytail: FK restrict DB menahan user berriwayat transaksi; guard self-delete ada di service.
        User::findOrFail($id)->delete();
    }
}
