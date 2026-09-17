<?php

namespace App\Repositories\Eloquent;

use App\Models\Member;
use App\Models\MemberCategory;
use App\Models\User;
use App\Repositories\Contracts\MemberRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MemberRepository implements MemberRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Member::query()
            ->with('category', 'officer:id,name')
            ->when($filters['trashed'] ?? null, fn ($query) => $query->onlyTrashed())
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('member_category_id', $categoryId))
            ->when($filters['officer_id'] ?? null, fn ($query, $officerId) => $query->where('officer_id', $officerId))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%"));
            })
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(int $id): Member
    {
        return Member::with('category', 'officer:id,name')->findOrFail($id);
    }

    public function create(array $data): Member
    {
        // Ploting otomatis: anggota baru langsung diplot ke petugas tertua (petugas1).
        // Dipasang di sini supaya kedua jalur pembuatan anggota ikut terploting —
        // pengurus lewat POST /members dan lewat POST /users (role anggota).
        $data['officer_id'] ??= User::where('role', 'petugas')
            ->orderBy('created_at')
            ->orderBy('id')
            ->value('id');

        return Member::create($data);
    }

    public function update(int $id, array $data): Member
    {
        $member = Member::findOrFail($id);
        $member->update($data);

        return $member->fresh();
    }

    public function delete(int $id): void
    {
        Member::findOrFail($id)->delete();
    }

    public function restore(int $id): Member
    {
        $member = Member::onlyTrashed()->findOrFail($id);
        $member->restore();

        return $member;
    }

    public function generateMemberCode(): string
    {
        $prefix = 'MBR-'.now()->format('Ym').'-';

        // withTrashed: kode anggota yang sudah diarsipkan tetap terpakai. Tanpa ini,
        // kode terakhir yang dihapus akan dipakai ulang dan menabrak unique constraint.
        $latest = Member::withTrashed()
            ->where('member_code', 'like', $prefix.'%')
            ->orderByDesc('member_code')
            ->lockForUpdate()
            ->first();

        $sequence = $latest ? ((int) substr($latest->member_code, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function getTypeCategoryId(string $type): ?int
    {
        return MemberCategory::where('name', $type)->value('id')
            ?? MemberCategory::query()->orderBy('id')->value('id');
    }

    public function listCategories(): Collection
    {
        return MemberCategory::orderBy('name')->get();
    }

    public function createCategory(array $data): MemberCategory
    {
        return MemberCategory::create($data);
    }
}
