<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupSchedule extends Model
{
    protected $fillable = ['member_id', 'days', 'slots', 'is_active'];

    protected function casts(): array
    {
        return [
            'days' => 'array',
            'slots' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
