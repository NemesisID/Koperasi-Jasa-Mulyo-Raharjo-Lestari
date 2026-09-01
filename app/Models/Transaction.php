<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'member_id',
        'category_id',
        'type',
        'amount',
        'description',
        'payment_method',
        'status',
        'transaction_date',
        'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function pickupItems(): HasMany
    {
        return $this->hasMany(PickupItem::class);
    }

    public function shuMembers(): HasMany
    {
        return $this->hasMany(ShuMember::class);
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeBerhasil($query)
    {
        return $query->where('status', 'berhasil');
    }
}
