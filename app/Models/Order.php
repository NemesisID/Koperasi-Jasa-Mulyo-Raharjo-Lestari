<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'idempotency_key',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_province',
        'shipping_city',
        'shipping_district',
        'shipping_postal_code',
        'shipping_address_detail',
        'total_weight_gram',
        'billable_weight_kg',
        'shipping_fee',
        'subtotal',
        'grand_total',
        'order_status',
        'payment_status',
        'payment_method',
        'payment_proof',
        'paid_at',
        'tracking_number',
        'notes',
    ];

    protected $casts = [
        'total_weight_gram' => 'integer',
        'billable_weight_kg' => 'integer',
        'shipping_fee' => 'integer',
        'subtotal' => 'integer',
        'grand_total' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }
}
