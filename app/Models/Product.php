<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'slug',
        'description',
        'weight_gram',
        'stock_qty',
        'image',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'weight_gram' => 'integer',
        'stock_qty' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function priceAudits(): HasMany
    {
        return $this->hasMany(PriceAudit::class);
    }

    /**
     * Get effective price for given tier ('retail' or 'distributor')
     */
    public function getPriceForTier(string $tier = 'retail'): ?int
    {
        $price = $this->prices->firstWhere('tier', $tier);
        if ($price) {
            return (int) $price->price;
        }

        // Fallback to retail if tier is distributor and not set
        $retail = $this->prices->firstWhere('tier', 'retail');
        return $retail ? (int) $retail->price : null;
    }
}
