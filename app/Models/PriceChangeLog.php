<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceChangeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'trash_category_id',
        'old_price_sorted',
        'new_price_sorted',
        'old_price_unsorted',
        'new_price_unsorted',
        'old_price_sell',
        'new_price_sell',
        'notes',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'old_price_sorted' => 'decimal:2',
            'new_price_sorted' => 'decimal:2',
            'old_price_unsorted' => 'decimal:2',
            'new_price_unsorted' => 'decimal:2',
            'old_price_sell' => 'decimal:2',
            'new_price_sell' => 'decimal:2',
        ];
    }

    public function trashCategory(): BelongsTo
    {
        return $this->belongsTo(TrashCategory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
