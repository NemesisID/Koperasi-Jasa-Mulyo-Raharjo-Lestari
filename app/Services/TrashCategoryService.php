<?php

namespace App\Services;

use App\Models\TrashCategory;
use App\Models\User;
use App\Repositories\Contracts\TrashCategoryRepositoryInterface;
use Illuminate\Support\Collection;

class TrashCategoryService
{
    /**
     * Potongan harga antar-jemput per kg: logam Rp2.000, non-logam Rp300.
     *
     * ponytail: hanya type `logam` dihitung logam (besi masuk non-logam); sesuaikan jika PRD menegaskan besi ikut potongan Rp2.000.
     */
    private const PICKUP_DEDUCTION_LOGAM = 2000;
    private const PICKUP_DEDUCTION_NON_LOGAM = 300;

    public function __construct(
        private readonly TrashCategoryRepositoryInterface $trashCategoryRepository,
    ) {}

    public function getCategories(array $filters): Collection
    {
        return $this->trashCategoryRepository->getAll($filters);
    }

    public function getCategory(int $id): TrashCategory
    {
        return $this->trashCategoryRepository->findById($id);
    }

    public function createCategory(array $data): TrashCategory
    {
        return $this->trashCategoryRepository->create($data);
    }

    public function updateCategory(int $id, array $data): TrashCategory
    {
        return $this->trashCategoryRepository->update($id, $data);
    }

    /**
     * Tarif per unit untuk lokasi jemput: harga dasar dikurangi potongan (logam Rp2.000, non-logam Rp300).
     */
    public function pickupPrice(TrashCategory $category, bool $isSorted): float
    {
        $base = $isSorted ? $category->price_sorted : $category->price_unsorted;
        $deduction = $category->type === 'logam'
            ? self::PICKUP_DEDUCTION_LOGAM
            : self::PICKUP_DEDUCTION_NON_LOGAM;

        return max(0, $base - $deduction);
    }

    /**
     * Papan info harga: perbandingan tarif antar gudang vs jemput + fluktuasi.
     */
    public function getPriceBoard(): Collection
    {
        return $this->trashCategoryRepository->getPriceBoardData()
            ->map(function (TrashCategory $category) {
                $deduction = $category->type === 'logam'
                    ? self::PICKUP_DEDUCTION_LOGAM
                    : self::PICKUP_DEDUCTION_NON_LOGAM;

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'unit' => $category->unit,
                    'price_sorted' => $category->price_sorted,
                    'price_unsorted' => $category->price_unsorted,
                    'price_sell' => $category->price_sell,
                    'price_member' => $category->price_member,
                    'pickup_price' => $this->pickupPrice($category, false),
                    'pickup_deduction' => $deduction,
                    'trend' => $this->resolveTrend($category),
                ];
            });
    }

    /**
     * Quick update harga harian + audit trail.
     */
    public function updateDailyPrice(int $id, array $data, User $actor): TrashCategory
    {
        return $this->trashCategoryRepository->updatePriceWithAudit($id, $data, $actor->id);
    }

    public function getPriceHistory(int $id): Collection
    {
        return $this->trashCategoryRepository->priceHistory($id);
    }

    private function resolveTrend(TrashCategory $category): string
    {
        $log = $category->latestPriceChange;

        if (! $log) {
            return 'stabil';
        }

        return match (true) {
            $log->new_price_unsorted > $log->old_price_unsorted => 'naik',
            $log->new_price_unsorted < $log->old_price_unsorted => 'turun',
            default => 'stabil',
        };
    }
}
