<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPengangkutan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'detail_pengangkutan';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'jadwal_angkut',
        'total_organik',
        'total_anorganik',
        'kecamatan',
        'desa',
        'dusun',
        'rw',
        'rt',
        'alamat',
    ];

    protected function casts(): array
    {
        return [
            'jadwal_angkut' => 'datetime',
            'total_organik' => 'decimal:2',
            'total_anorganik' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
