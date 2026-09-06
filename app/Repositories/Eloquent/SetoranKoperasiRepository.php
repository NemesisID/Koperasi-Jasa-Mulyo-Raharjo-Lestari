<?php

namespace App\Repositories\Eloquent;

use App\Models\SetoranKoperasi;
use App\Repositories\Contracts\SetoranKoperasiRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SetoranKoperasiRepository implements SetoranKoperasiRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return SetoranKoperasi::query()
            ->with('user:id,name,username')
            ->when($filters['label'] ?? null, fn ($query, $label) => $query->where('label', $label))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id): SetoranKoperasi
    {
        return SetoranKoperasi::with('user:id,name,username')->findOrFail($id);
    }

    public function create(array $data): SetoranKoperasi
    {
        return SetoranKoperasi::create($data);
    }

    public function updateStatus(string $id, string $status): SetoranKoperasi
    {
        $setoran = SetoranKoperasi::findOrFail($id);
        $setoran->update(['status' => $status]);

        return $this->findById($id);
    }

    public function sumSettledByLabel(int $userId, string $label): float
    {
        return (float) SetoranKoperasi::where('user_id', $userId)
            ->where('label', $label)
            ->where('status', 'SELESAI')
            ->sum('jumlah');
    }
}
