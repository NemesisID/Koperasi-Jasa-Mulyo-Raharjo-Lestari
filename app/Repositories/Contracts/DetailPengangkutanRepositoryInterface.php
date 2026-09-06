<?php

namespace App\Repositories\Contracts;

use App\Models\DetailPengangkutan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DetailPengangkutanRepositoryInterface
{
    /**
     * Log ritase (filter wilayah desa/rw/rt & rentang tanggal).
     */
    public function paginate(array $filters): LengthAwarePaginator;

    public function create(array $data): DetailPengangkutan;
}
