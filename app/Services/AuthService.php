<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Member;
use App\Models\User;
use App\Repositories\Contracts\MemberRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly MemberRepositoryInterface $memberRepository,
        private readonly SavingsService $savingsService,
    ) {}

    /**
     * Autentikasi user via email/username dan terbitkan Sanctum token.
     *
     * @param  array{identity: string, password: string}  $credentials
     * @return array{token: string, user: User}
     */
    public function authenticate(array $credentials, string $deviceName): array
    {
        $user = $this->userRepository->findByEmailOrUsername($credentials['identity']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'identity' => 'Email/username atau password salah.',
            ]);
        }

        return [
            'token' => $user->createToken($deviceName)->plainTextToken,
            'user' => $user->load('member.category'),
        ];
    }

    /**
     * Registrasi calon anggota (role anggota).
     * Status default nonaktif (menunggu verifikasi) untuk registrasi mandiri;
     * pengurus membuat akun anggota langsung aktif.
     *
     * @param  array{member_types: array<int, string>}  $data
     * @return array{user: User, member: Member}
     */
    public function registerMember(array $data, string $status = 'nonaktif'): array
    {
        [$user, $member] = DB::transaction(function () use ($data, $status): array {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'anggota',
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            $memberTypes = array_values(array_unique($data['member_types']));

            $member = $this->memberRepository->create([
                'user_id' => $user->id,
                // Kategori pertama jadi kategori utama (kolom wajib); sisanya di kolom json.
                'member_category_id' => $this->memberRepository->getTypeCategoryId($memberTypes[0]),
                'categories' => $memberTypes,
                'member_code' => $this->memberRepository->generateMemberCode(),
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $status,
                'join_date' => now()->toDateString(),
            ]);

            // Simpanan pokok Rp50.000 otomatis saat akun anggota dibuat.
            $this->savingsService->recordInitialPokok($member);

            return [$user, $member];
        });

        return [
            'user' => $user->load('member.category'),
            'member' => $member->load('category'),
        ];
    }

    /**
     * Perbarui informasi kontak profil pengguna yang sedang login.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user = $this->userRepository->update($user->id, [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        return $user->load('member.category');
    }

    /**
     * Ganti password setelah memverifikasi password saat ini.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new BusinessLogicException('Password saat ini tidak sesuai.');
        }

        $this->userRepository->update($user->id, ['password' => $newPassword]);
    }

    /**
     * Cabut personal access token yang sedang dipakai.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
