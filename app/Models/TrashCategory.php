<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrashCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'unit',
        'price_sorted',
        'price_unsorted',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_sorted' => 'decimal:2',
            'price_unsorted' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function pickupItems(): HasMany
    {
        return $this->hasMany(PickupItem::class, 'category_id');
    }

    public function priceChangeLogs(): HasMany
    {
        return $this->hasMany(PriceChangeLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
