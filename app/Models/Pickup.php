<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pickup extends Model
{
    use HasFactory;

    protected $fillable = [
        'officer_id',
        'member_id',
        'location_type',
        'is_sorted',
        'scheduled_at',
        'completed_at',
        'status',
        'notes',
        'total_gross',
        'total_fee',
        'total_net',
        'photo_path',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'is_sorted' => 'boolean',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PickupItem::class);
    }

    public function scopeMenunggu($query)
    {
        return $query->where('status', 'menunggu');
    }

    public function scopeSelesai($query)
    {
        return $query->where('status', 'selesai');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset(\Illuminate\Support\Facades\Storage::url($this->photo_path)) : null;
    }
}
