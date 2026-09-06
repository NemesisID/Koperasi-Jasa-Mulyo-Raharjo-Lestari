<?php

namespace App\Repositories\Eloquent;

use App\Models\DetailPengangkutan;
use App\Repositories\Contracts\DetailPengangkutanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DetailPengangkutanRepository implements DetailPengangkutanRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return DetailPengangkutan::query()
            ->with('user:id,name,username')
            ->when($filters['desa'] ?? null, fn ($query, $desa) => $query->where('desa', $desa))
            ->when($filters['rw'] ?? null, fn ($query, $rw) => $query->where('rw', $rw))
            ->when($filters['rt'] ?? null, fn ($query, $rt) => $query->where('rt', $rt))
            ->when($filters['start_date'] ?? null, fn ($query, $start) => $query->whereDate('jadwal_angkut', '>=', $start))
            ->when($filters['end_date'] ?? null, fn ($query, $end) => $query->whereDate('jadwal_angkut', '<=', $end))
            ->orderByDesc('jadwal_angkut')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): DetailPengangkutan
    {
        return DetailPengangkutan::create($data);
    }
}
