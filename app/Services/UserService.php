<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\User;
use App\Repositories\Contracts\MemberRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly MemberRepositoryInterface $memberRepository,
        private readonly AuthService $authService,
    ) {}

    public function getUsersPaginated(array $filters): LengthAwarePaginator
    {
        return $this->userRepository->paginate($filters);
    }

    public function getUser(int $id): User
    {
        return $this->userRepository->findWithMember($id);
    }

    public function createUser(array $data): User
    {
        // Akun anggota lewat alur registrasi member: kode anggota, kategori rumah/pasar,
        // dan simpanan pokok otomatis — dibuat pengurus jadi langsung aktif.
        if (($data['role'] ?? null) === 'anggota') {
            return $this->authService->registerMember($data, 'aktif')['user'];
        }

        return $this->userRepository->create($data);
    }

    public function updateUser(int $id, array $data): User
    {
        $user = $this->userRepository->update($id, $data);

        // Sinkron kategori (rumah/pasar) + kontak anggota bila akun ini anggota.
        if ($user->role === 'anggota' && $user->member && ! empty($data['member_types'])) {
            $memberTypes = array_values(array_unique($data['member_types']));

            $user->member->update([
                'member_category_id' => $this->memberRepository->getTypeCategoryId($memberTypes[0]),
                'categories' => $memberTypes,
                'name' => $user->name,
                'phone' => $user->phone,
                'address' => $user->address,
            ]);
        }

        return $user->load('member.category');
    }

    public function deleteUser(User $actor, int $id): void
    {
        if ($actor->id === $id) {
            throw new BusinessLogicException('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $this->userRepository->delete($id);
    }
}
