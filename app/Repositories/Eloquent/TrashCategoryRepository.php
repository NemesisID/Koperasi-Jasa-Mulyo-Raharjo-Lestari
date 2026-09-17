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

        // Edit form (PUT) juga bisa mengubah harga — catat ke audit log seperti
        // PATCH /price supaya riwayat harga lengkap (issue #2).
        $priceChanged = collect(['price_unsorted', 'price_sell'])
            ->contains(fn (string $field) => isset($data[$field]) && (float) $data[$field] !== (float) $category->{$field});

        if ($priceChanged) {
            PriceChangeLog::create([
                'trash_category_id' => $category->id,
                'old_price_sorted' => $category->price_sorted,
                'new_price_sorted' => $data['price_sorted'] ?? $category->price_sorted,
                'old_price_unsorted' => $category->price_unsorted,
                'new_price_unsorted' => $data['price_unsorted'] ?? $category->price_unsorted,
                'old_price_sell' => $category->price_sell,
                'new_price_sell' => $data['price_sell'] ?? $category->price_sell,
                'changed_by' => $data['changed_by'] ?? null,
            ]);
        }

        $category->update(collect($data)->except('changed_by')->all());

        return $category->fresh();
    }

    public function updatePriceWithAudit(int $id, array $priceData, int $userId): TrashCategory
    {
        return DB::transaction(function () use ($id, $priceData, $userId): TrashCategory {
            $category = $this->findById($id);

            // price_sorted tidak lagi diisi form — pertahankan nilai lama bila tidak dikirim.
            $priceSorted = $priceData['price_sorted'] ?? $category->price_sorted;

            PriceChangeLog::create([
                'trash_category_id' => $category->id,
                'old_price_sorted' => $category->price_sorted,
                'new_price_sorted' => $priceSorted,
                'old_price_unsorted' => $category->price_unsorted,
                'new_price_unsorted' => $priceData['price_unsorted'],
                'old_price_sell' => $category->price_sell,
                'new_price_sell' => $priceData['price_sell'],
                'notes' => $priceData['notes'] ?? null,
                'changed_by' => $userId,
            ]);

            $category->update([
                'price_sorted' => $priceSorted,
                'price_unsorted' => $priceData['price_unsorted'],
                'price_sell' => $priceData['price_sell'],
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
