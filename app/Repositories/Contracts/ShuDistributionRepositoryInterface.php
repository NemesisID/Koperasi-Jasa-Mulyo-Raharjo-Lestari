<?php

namespace App\Repositories\Contracts;

use App\Models\ShuDistribution;
use Illuminate\Support\Collection;

interface ShuDistributionRepositoryInterface
{
    /**
     * Riwayat periode SHU (terbaru dulu).
     */
    public function all(): Collection;

    public function findById(int $id): ShuDistribution;

    /**
     * Simpan draft distribusi (status draft, recipient_count dari jumlah baris anggota).
     */
    public function createDraft(array $data): ShuDistribution;

    /**
     * Simpan rincian per anggota untuk sebuah distribusi.
     */
    public function saveMemberRows(int $distributionId, array $rows): void;
}
