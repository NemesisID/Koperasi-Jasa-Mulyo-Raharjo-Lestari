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
        'price_sell',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_sorted' => 'decimal:2',
            'price_unsorted' => 'decimal:2',
            'price_sell' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Harga untuk anggota: harga jual dipotong 20% koperasi.
     */
    public function getPriceMemberAttribute(): float
    {
        return round((float) $this->price_sell * 0.80, 2);
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
