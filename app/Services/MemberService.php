<?php

namespace App\Services;

use App\Models\Member;
use App\Repositories\Contracts\MemberRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberService
{
    public function __construct(
        private readonly MemberRepositoryInterface $memberRepository,
    ) {}

    public function getMembersPaginated(array $filters): LengthAwarePaginator
    {
        return $this->memberRepository->paginate($filters);
    }

    public function getMember(int $id): Member
    {
        return $this->memberRepository->findById($id);
    }

    public function createMember(array $data): Member
    {
        $data['member_code'] = $this->memberRepository->generateMemberCode();
        $data['join_date'] ??= now()->toDateString();
        $data['status'] ??= 'aktif';

        return $this->memberRepository->create($data);
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
