<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'tier',
        'old_price',
        'new_price',
        'admin_id',
        'notes',
    ];

    protected $casts = [
        'old_price' => 'integer',
        'new_price' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
