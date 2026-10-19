<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            [
                'key' => 'site_name',
                'value' => 'Koperasi Jasa Mulyo Raharjo Lestari',
                'type' => 'string',
                'group' => 'general',
            ],
            [
                'key' => 'site_tagline',
                'value' => 'Solusi Sirkular Ekonomi Berbasis Komunitas dan Bank Sampah',
                'type' => 'string',
                'group' => 'general',
            ],
            [
                'key' => 'site_logo',
                'value' => '/logo.png',
                'type' => 'image',
                'group' => 'general',
            ],

            // Contact
            [
                'key' => 'contact_address',
                'value' => 'Kantor Pusat Koperasi Mulyo Raharjo, Jl. Raya Desa Mulyo No. 45, Jawa Timur',
                'type' => 'string',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_phone',
                'value' => '081234567890',
                'type' => 'string',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_whatsapp',
                'value' => '6281234567890',
                'type' => 'string',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_email',
                'value' => 'info@koperasimulyoraharjo.com',
                'type' => 'string',
                'group' => 'contact',
            ],

            // Payment / Bank Accounts
            [
                'key' => 'payment_bank_accounts',
                'value' => json_encode([
                    [
                        'bank_name' => 'Bank Mandiri',
                        'account_number' => '1440019283741',
                        'account_holder' => 'Koperasi Jasa Mulyo Raharjo Lestari',
                    ],
                    [
                        'bank_name' => 'Bank BRI',
                        'account_number' => '012301002345501',
                        'account_holder' => 'Koperasi Mulyo Raharjo',
                    ],
                    [
                        'bank_name' => 'Bank Jatim',
                        'account_number' => '0291008899',
                        'account_holder' => 'KJPS Mulyo Raharjo',
                    ],
                ]),
                'type' => 'json',
                'group' => 'payment',
            ],
            [
                'key' => 'payment_qris_image',
                'value' => 'https://via.placeholder.com/400x400.png?text=QRIS+KJPS+Mulyo+Raharjo',
                'type' => 'image',
                'group' => 'payment',
            ],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
