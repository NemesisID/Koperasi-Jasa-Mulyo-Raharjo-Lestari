<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'member_category_id',
        'member_code',
        'name',
        'address',
        'phone',
        'status',
        'join_date',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MemberCategory::class, 'member_category_id');
    }

    public function pickups(): HasMany
    {
        return $this->hasMany(Pickup::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function shuMembers(): HasMany
    {
        return $this->hasMany(ShuMember::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }
}
