<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use Illuminate\Database\Seeder;

class ShippingRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            // Jawa Timur
            [
                'province' => 'Jawa Timur',
                'city' => 'Malang',
                'district' => null,
                'rate_per_kg' => 10000,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'is_active' => true,
            ],
            [
                'province' => 'Jawa Timur',
                'city' => 'Surabaya',
                'district' => null,
                'rate_per_kg' => 11000,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'is_active' => true,
            ],
            [
                'province' => 'Jawa Timur',
                'city' => 'Sidoarjo',
                'district' => null,
                'rate_per_kg' => 11000,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'is_active' => true,
            ],
            [
                'province' => 'Jawa Timur',
                'city' => 'Pasuruan',
                'district' => null,
                'rate_per_kg' => 10000,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'is_active' => true,
            ],
            [
                'province' => 'Jawa Timur',
                'city' => 'Kediri',
                'district' => null,
                'rate_per_kg' => 12000,
                'estimated_days_min' => 1,
                'estimated_days_max' => 3,
                'is_active' => true,
            ],
            // DKI Jakarta
            [
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Selatan',
                'district' => null,
                'rate_per_kg' => 18000,
                'estimated_days_min' => 2,
                'estimated_days_max' => 3,
                'is_active' => true,
            ],
            [
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Pusat',
                'district' => null,
                'rate_per_kg' => 18000,
                'estimated_days_min' => 2,
                'estimated_days_max' => 3,
                'is_active' => true,
            ],
            // Jawa Barat
            [
                'province' => 'Jawa Barat',
                'city' => 'Bandung',
                'district' => null,
                'rate_per_kg' => 17000,
                'estimated_days_min' => 2,
                'estimated_days_max' => 3,
                'is_active' => true,
            ],
            // Jawa Tengah & DIY
            [
                'province' => 'Jawa Tengah',
                'city' => 'Semarang',
                'district' => null,
                'rate_per_kg' => 14000,
                'estimated_days_min' => 2,
                'estimated_days_max' => 3,
                'is_active' => true,
            ],
            [
                'province' => 'DI Yogyakarta',
                'city' => 'Yogyakarta',
                'district' => null,
                'rate_per_kg' => 14000,
                'estimated_days_min' => 2,
                'estimated_days_max' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($rates as $rate) {
            ShippingRate::updateOrCreate(
                [
                    'province' => $rate['province'],
                    'city' => $rate['city'],
                    'district' => $rate['district'],
                ],
                $rate
            );
        }
    }
}
