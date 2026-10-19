<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'province',
        'city',
        'district',
        'rate_per_kg',
        'estimated_days_min',
        'estimated_days_max',
        'is_active',
    ];

    protected $casts = [
        'rate_per_kg' => 'integer',
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Cari rate yang paling cocok berdasarkan province, city, dan district
     */
    public static function findRate(string $province, string $city, ?string $district = null): ?self
    {
        $query = static::where('is_active', true)
            ->whereRaw('LOWER(province) = ?', [strtolower(trim($province))])
            ->whereRaw('LOWER(city) = ?', [strtolower(trim($city))]);

        if ($district) {
            $districtMatch = (clone $query)->whereRaw('LOWER(district) = ?', [strtolower(trim($district))])->first();
            if ($districtMatch) {
                return $districtMatch;
            }
        }

        // Fallback ke tarif kota (district null)
        return $query->whereNull('district')->first() ?? static::where('is_active', true)->first();
    }
}
