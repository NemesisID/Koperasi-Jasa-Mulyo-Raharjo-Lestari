<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use App\Repositories\Contracts\MemberRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberService
{
    public function __construct(
        private readonly MemberRepositoryInterface $memberRepository,
        private readonly SavingsService $savingsService,
    ) {}

    public function getMembersPaginated(array $filters): LengthAwarePaginator
    {
        return $this->memberRepository->paginate($filters);
    }

    public function getMember(int $id): Member
    {
        return $this->memberRepository->findById($id);
    }

    public function createMember(array $data, ?User $handler = null): Member
    {
        $data['member_code'] = $this->memberRepository->generateMemberCode();
        $data['join_date'] ??= now()->toDateString();
        $data['status'] ??= 'aktif';

        $member = $this->memberRepository->create($data);

        // Simpanan pokok Rp50.000 per kategori member otomatis untuk anggota baru.
        $this->savingsService->recordInitialPokok($member, $handler);

        return $member;
    }

    public function updateMember(int $id, array $data): Member
    {
        return $this->memberRepository->update($id, $data);
    }

    public function updateStatus(int $id, string $status): Member
    {
        // ponytail: kolom `notes` tidak ada di tabel members — alasan status tidak dipersist, tambahkan kolom jika butuh audit.
        return $this->memberRepository->update($id, ['status' => $status]);
    }

    public function listCategories()
    {
        return $this->memberRepository->listCategories();
    }

    public function createCategory(array $data)
    {
        return $this->memberRepository->createCategory($data);
    }
}
