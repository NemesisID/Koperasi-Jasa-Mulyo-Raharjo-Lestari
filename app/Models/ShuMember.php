<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShuMember extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'shu_distribution_id',
        'member_id',
        'simpanan_pokok_amount',
        'simpanan_wajib_amount',
        'participation_amount',
        'total_shu',
        'status',
        'paid_at',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'simpanan_pokok_amount' => 'decimal:2',
            'simpanan_wajib_amount' => 'decimal:2',
            'participation_amount' => 'decimal:2',
            'total_shu' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(ShuDistribution::class, 'shu_distribution_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
