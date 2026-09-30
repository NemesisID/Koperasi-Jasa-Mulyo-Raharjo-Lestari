<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'officer_id',
        'member_category_id',
        'categories',
        'member_code',
        'name',
        'address',
        'address_rumah',
        'address_pasar',
        'phone',
        'status',
        'join_date',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'categories' => 'array',
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

    /**
     * Petugas yang di-plot menangani anggota ini (per alamat).
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
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

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function withdrawRequests(): HasMany
    {
        return $this->hasMany(WithdrawRequest::class);
    }

    public function setoranKoperasi(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(SetoranKoperasi::class, User::class, 'id', 'user_id', 'user_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    public function getCurrentBalanceAttribute(): float
    {
        $walletService = app(\App\Services\WalletService::class);
        return (float) ($walletService->getMemberWalletSummary($this->id)['current_balance'] ?? 0);
    }

    public function getAvailableBalanceAttribute(): float
    {
        $walletService = app(\App\Services\WalletService::class);
        return (float) ($walletService->getMemberWalletSummary($this->id)['available_balance'] ?? 0);
    }
}
