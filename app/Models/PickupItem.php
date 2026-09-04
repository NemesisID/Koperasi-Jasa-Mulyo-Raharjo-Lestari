<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'pickup_id',
        'category_id',
        'weight_kg',
        'unit_count',
        'total_value',
        'deposit_date',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'unit_count' => 'integer',
            'total_value' => 'decimal:2',
            'deposit_date' => 'datetime',
        ];
    }

    public function pickup(): BelongsTo
    {
        return $this->belongsTo(Pickup::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TrashCategory::class, 'category_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
