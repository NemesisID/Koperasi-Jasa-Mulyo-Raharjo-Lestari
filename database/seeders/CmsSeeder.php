<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\CmsSection;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Halaman 'home'
        $home = CmsPage::firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Beranda — Koperasi Jasa Mulyo Raharjo Lestari',
                'meta_description' => 'Solusi sirkular ekonomi terpadu: pengelolaan sampah, bank sampah digital, pupuk organik, pakan maggot, dan e-commerce ramah lingkungan.',
                'is_published' => true,
            ]
        );

        $homeSections = [
            [
                'key' => 'hero',
                'title' => 'Wujudkan Desa Bersih, Berdaya, dan Mandiri Ekonomi',
                'content' => [
                    'headline' => 'Transformasi Sampah Menjadi Berkah dan Nilai Ekonomi Sirkular',
                    'subheadline' => 'Koperasi Jasa Mulyo Raharjo Lestari mendampingi masyarakat dan pelaku usaha dalam pengelolaan sampah terintegrasi, tabungan bank sampah digital, dan produk daur ulang berkualitas tinggi.',
                    'cta_primary' => [
                        'label' => 'Jelajahi Produk Kami',
                        'link' => '/products',
                    ],
                    'cta_secondary' => [
                        'label' => 'Daftar Nasabah Bank Sampah',
                        'link' => '/register',
                    ],
                    'banner_image' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=1200&q=80',
                ],
                'sort_order' => 1,
            ],
            [
                'key' => 'about_preview',
                'title' => 'Tentang Koperasi Kami',
                'content' => [
                    'description' => 'Didirikan dengan semangat gotong royong warga untuk mengatasi persoalan limbah dan menciptakan lapangan kerja baru melalui budidaya maggot BSF, produksi pupuk organik, dan kerajinan tangan daur ulang.',
                    'stats' => [
                        ['label' => 'Nasabah Aktif', 'value' => '1.250+'],
                        ['label' => 'Sampah Terkelola / Bulan', 'value' => '45 Ton'],
                        ['label' => 'Produk Daur Ulang', 'value' => '20+ Item'],
                        ['label' => 'Mitra Pengangkutan', 'value' => '15 Titik Pasar'],
                    ],
                ],
                'sort_order' => 2,
            ],
            [
                'key' => 'services',
                'title' => 'Layanan Unggulan',
                'content' => [
                    'items' => [
                        [
                            'icon' => 'recycle',
                            'title' => 'Bank Sampah Digital',
                            'desc' => 'Pencatatan penimbangan transparan, saldo real-time, dan kemudahan pencairan kas untuk warga.',
                        ],
                        [
                            'icon' => 'truck',
                            'title' => 'Penjemputan Sampah Terjadwal',
                            'desc' => 'Armada armada pengangkutan rutin untuk rumah tangga, perkantoran, dan pasar tradisional.',
                        ],
                        [
                            'icon' => 'shopping-bag',
                            'title' => 'Katalog Produk Organik & Daur Ulang',
                            'desc' => 'Distribusi pupuk kompos, cairan biourine, tepung maggot pakan ternak, dan kerajinan ramah lingkungan.',
                        ],
                    ],
                ],
                'sort_order' => 3,
            ],
        ];

        foreach ($homeSections as $sec) {
            CmsSection::updateOrCreate(
                [
                    'cms_page_id' => $home->id,
                    'key' => $sec['key'],
                ],
                [
                    'title' => $sec['title'],
                    'content' => $sec['content'],
                    'sort_order' => $sec['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        // 2. Halaman 'about'
        $about = CmsPage::firstOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'Tentang Kami — Koperasi Jasa Mulyo Raharjo Lestari',
                'meta_description' => 'Mengenal sejarah, visi-misi, susunan pengurus, dan dedikasi Koperasi Mulyo Raharjo Lestari bagi lingkungan lestari.',
                'is_published' => true,
            ]
        );

        $aboutSections = [
            [
                'key' => 'vision_mission',
                'title' => 'Visi dan Misi',
                'content' => [
                    'vision' => 'Menjadi pusat gerakan ekonomi sirkular desa berbasis koperasi yang mandiri, berkeadilan, dan berkelanjutan.',
                    'missions' => [
                        'Membangun sistem pengelolaan sampah terintegrasi hulu-hilir yang minim residu.',
                        'Memberikan nilai tambah finansial langsung kepada seluruh anggota melalui tabungan sampah dan pembagian SHU.',
                        'Menghasilkan pupuk organik dan pakan ternak terjangkau demi ketahanan pangan lokal.',
                    ],
                ],
                'sort_order' => 1,
            ],
        ];

        foreach ($aboutSections as $sec) {
            CmsSection::updateOrCreate(
                [
                    'cms_page_id' => $about->id,
                    'key' => $sec['key'],
                ],
                [
                    'title' => $sec['title'],
                    'content' => $sec['content'],
                    'sort_order' => $sec['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
