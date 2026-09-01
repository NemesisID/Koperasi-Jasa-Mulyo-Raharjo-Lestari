<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShuDistribution extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'year',
        'total_shu',
        'reserve_amount',
        'distributed_amount',
        'recipient_count',
        'status',
        'distribution_date',
        'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'total_shu' => 'decimal:2',
            'reserve_amount' => 'decimal:2',
            'distributed_amount' => 'decimal:2',
            'distribution_date' => 'date',
            'recipient_count' => 'integer',
        ];
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function shuMembers(): HasMany
    {
        return $this->hasMany(ShuMember::class);
    }

    public function scopeYear($query, int $year)
    {
        return $query->where('year', $year);
    }
}
