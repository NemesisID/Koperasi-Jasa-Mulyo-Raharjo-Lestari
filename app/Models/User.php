<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function setoranKoperasi(): HasMany
    {
        return $this->hasMany(SetoranKoperasi::class);
    }

    public function detailPengangkutan(): HasMany
    {
        return $this->hasMany(DetailPengangkutan::class);
    }

    public function handledTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'handled_by');
    }

    public function assignedPickups(): HasMany
    {
        return $this->hasMany(Pickup::class, 'officer_id');
    }

    public function createdReports(): HasMany
    {
        return $this->hasMany(Report::class, 'created_by');
    }

    public function handledShuDistributions(): HasMany
    {
        return $this->hasMany(ShuDistribution::class, 'handled_by');
    }

    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
