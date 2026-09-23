<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use HasFactory;

    protected $table = 'receipts';

    protected $fillable = [
        'pickup_id',
        'receipt_number',
        'member_id',
        'officer_id',
        'member_name',
        'member_code',
        'officer_name',
        'location_type',
        'location_label',
        'items_payload',
        'nota_data',
        'total_gross',
        'total_fee',
        'total_net',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'items_payload' => 'array',
            'nota_data' => 'array',
            'total_gross' => 'decimal:2',
            'total_fee' => 'decimal:2',
            'total_net' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function pickup(): BelongsTo
    {
        return $this->belongsTo(Pickup::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }
}
