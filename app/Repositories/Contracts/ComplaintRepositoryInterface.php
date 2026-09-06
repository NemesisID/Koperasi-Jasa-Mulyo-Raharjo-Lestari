<?php

namespace App\Repositories\Contracts;

use App\Models\Complaint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ComplaintRepositoryInterface
{
    /**
     * Daftar komplain (filter status / member_id / pickup_id).
     */
    public function paginate(array $filters): LengthAwarePaginator;

    public function findById(int $id): Complaint;

    public function create(array $data): Complaint;

    public function update(int $id, array $data): Complaint;
}
