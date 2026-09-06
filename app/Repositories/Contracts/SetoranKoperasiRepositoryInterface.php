<?php

namespace App\Repositories\Contracts;

use App\Models\SetoranKoperasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SetoranKoperasiRepositoryInterface
{
    /**
     * Rekap setoran (filter label / status / user_id).
     */
    public function paginate(array $filters): LengthAwarePaginator;

    public function findById(string $id): SetoranKoperasi;

    public function create(array $data): SetoranKoperasi;

    public function updateStatus(string $id, string $status): SetoranKoperasi;

    /**
     * Total setoran selesai seorang user untuk satu label.
     */
    public function sumSettledByLabel(int $userId, string $label): float;
}
