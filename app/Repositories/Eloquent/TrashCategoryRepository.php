<?php

namespace App\Repositories\Eloquent;

use App\Models\PriceChangeLog;
use App\Models\TrashCategory;
use App\Repositories\Contracts\TrashCategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrashCategoryRepository implements TrashCategoryRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        return TrashCategory::query()
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when(isset($filters['is_active']), fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->orderBy('name')
            ->get();
    }

    public function getPriceBoardData(): Collection
    {
        $categories = TrashCategory::active()->orderBy('name')->get();

        // ponytail: ambil semua log lalu unique di PHP — cukup untuk skala katalog puluhan item; pakai window function jika membludak.
        $latestLogs = PriceChangeLog::whereIn('trash_category_id', $categories->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->unique('trash_category_id')
            ->keyBy('trash_category_id');

        return $categories->each(
            fn ($category) => $category->setRelation('latestPriceChange', $latestLogs->get($category->id)),
        );
    }

    public function findById(int $id): TrashCategory
    {
        return TrashCategory::findOrFail($id);
    }

    public function create(array $data): TrashCategory
    {
        return TrashCategory::create($data);
    }

    public function update(int $id, array $data): TrashCategory
    {
        $category = $this->findById($id);
        $category->update($data);

        return $category->fresh();
    }

    public function updatePriceWithAudit(int $id, array $priceData, int $userId): TrashCategory
    {
        return DB::transaction(function () use ($id, $priceData, $userId): TrashCategory {
            $category = $this->findById($id);

            PriceChangeLog::create([
                'trash_category_id' => $category->id,
                'old_price_sorted' => $category->price_sorted,
                'new_price_sorted' => $priceData['price_sorted'],
                'old_price_unsorted' => $category->price_unsorted,
                'new_price_unsorted' => $priceData['price_unsorted'],
                'notes' => $priceData['notes'] ?? null,
                'changed_by' => $userId,
            ]);

            $category->update([
                'price_sorted' => $priceData['price_sorted'],
                'price_unsorted' => $priceData['price_unsorted'],
            ]);

            return $category->fresh();
        });
    }

    public function priceHistory(int $id): Collection
    {
        return PriceChangeLog::with('user:id,name,username')
            ->where('trash_category_id', $id)
            ->orderByDesc('id')
            ->get();
    }
}
