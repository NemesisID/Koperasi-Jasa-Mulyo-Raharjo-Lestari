<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function getUsersPaginated(array $filters): LengthAwarePaginator
    {
        return $this->userRepository->paginate($filters);
    }

    public function getUser(int $id): User
    {
        return $this->userRepository->findById($id);
    }

    public function createUser(array $data): User
    {
        return $this->userRepository->create($data);
    }

    public function updateUser(int $id, array $data): User
    {
        return $this->userRepository->update($id, $data);
    }

    public function deleteUser(User $actor, int $id): void
    {
        if ($actor->id === $id) {
            throw new BusinessLogicException('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $this->userRepository->delete($id);
    }
}
