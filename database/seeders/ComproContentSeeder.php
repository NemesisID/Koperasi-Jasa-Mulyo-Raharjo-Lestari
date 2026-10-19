<?php

namespace Database\Seeders;

use App\Models\ComproContent;
use Illuminate\Database\Seeder;

class ComproContentSeeder extends Seeder
{
    public function run(): void
    {
        $contents = [
            // ==================== SECTION: HERO ====================
            [
                'page' => 'welcome',
                'section' => 'hero',
                'key' => 'judul',
                'type' => 'text',
                'value' => 'Solusi Sirkular Ekonomi Berbasis Komunitas & Lingkungan',
                'order' => 1,
            ],
            [
                'page' => 'welcome',
                'section' => 'hero',
                'key' => 'deskripsi',
                'type' => 'text',
                'value' => 'Koperasi Jasa Mulyo Raharjo Lestari menghubungkan warga, pasar, dan industri dalam pengelolaan sampah terintegrasi, tabungan bank sampah digital, serta produk daur ulang berkualitas tinggi.',
                'order' => 2,
            ],
            [
                'page' => 'welcome',
                'section' => 'hero',
                'key' => 'background_image',
                'type' => 'image',
                'value' => 'welcome/hero_banner_koperasi.webp',
                'order' => 3,
            ],
            [
                'page' => 'welcome',
                'section' => 'hero',
                'key' => 'cta_primary',
                'type' => 'repeater',
                'value' => json_encode([
                    'text' => 'Katalog Produk',
                    'link' => '/products',
                ]),
                'order' => 4,
            ],
            [
                'page' => 'welcome',
                'section' => 'hero',
                'key' => 'cta_secondary',
                'type' => 'repeater',
                'value' => json_encode([
                    'text' => 'Daftar Nasabah',
                    'link' => '/register',
                ]),
                'order' => 5,
            ],

            // ==================== SECTION: ABOUT ====================
            [
                'page' => 'welcome',
                'section' => 'about',
                'key' => 'judul',
                'type' => 'text',
                'value' => 'Tentang Koperasi Jasa Mulyo Raharjo Lestari',
                'order' => 1,
            ],
            [
                'page' => 'welcome',
                'section' => 'about',
                'key' => 'deskripsi',
                'type' => 'richtext',
                'value' => '<p>Koperasi Jasa Mulyo Raharjo Lestari berdiri sebagai wadah kolaboratif masyarakat untuk mengolah potensi sampah desa menjadi komoditas bernilai tambah. Melalui sistem bank sampah berprinsip syariah dan gotong royong, anggota memperoleh manfaat tabungan simpanan, bagi hasil sisa usaha (SHU), serta lingkungan yang asri dan minim residu.</p>',
                'order' => 2,
            ],
            [
                'page' => 'welcome',
                'section' => 'about',
                'key' => 'video_url',
                'type' => 'url',
                'value' => 'https://www.youtube.com/embed/sample-video-koperasi',
                'order' => 3,
            ],
            [
                'page' => 'welcome',
                'section' => 'about',
                'key' => 'statistik',
                'type' => 'repeater',
                'value' => json_encode([
                    ['label' => 'Anggota Aktif', 'value' => '1.250+'],
                    ['label' => 'Sampah Dikelola/Bulan', 'value' => '45 Ton'],
                    ['label' => 'Mitra Unit Pasar', 'value' => '15 Lokasi'],
                    ['label' => 'Produk Daur Ulang', 'value' => '20+ Varian'],
                ]),
                'order' => 4,
            ],

            // ==================== SECTION: INSTITUSI / MITRA ====================
            [
                'page' => 'welcome',
                'section' => 'institusi',
                'key' => 'judul',
                'type' => 'text',
                'value' => 'Mitra & Kolaborator Strategis',
                'order' => 1,
            ],
            [
                'page' => 'welcome',
                'section' => 'institusi',
                'key' => 'deskripsi',
                'type' => 'text',
                'value' => 'Bersinergi bersama pemerintah daerah, perguruan tinggi, kelompok tani, dan asosiasi industri daur ulang nasional.',
                'order' => 2,
            ],
            [
                'page' => 'welcome',
                'section' => 'institusi',
                'key' => 'daftar_baris_1',
                'type' => 'repeater',
                'value' => json_encode([
                    ['nama' => 'Dinas Lingkungan Hidup Kab. Malang', 'logo' => 'welcome/mitra_dlh.webp'],
                    ['nama' => 'Universitas Brawijaya', 'logo' => 'welcome/mitra_ub.webp'],
                    ['nama' => 'Pemerintah Desa Mulyo', 'logo' => 'welcome/mitra_desa.webp'],
                ]),
                'order' => 3,
            ],
            [
                'page' => 'welcome',
                'section' => 'institusi',
                'key' => 'daftar_baris_2',
                'type' => 'repeater',
                'value' => json_encode([
                    ['nama' => 'UD Sapu Jagad Ponorogo', 'logo' => 'welcome/mitra_sapujagad.webp'],
                    ['nama' => 'Asosiasi Bank Sampah Indonesia', 'logo' => 'welcome/mitra_asobsi.webp'],
                    ['nama' => 'Kelompok Tani Lestari', 'logo' => 'welcome/mitra_poktan.webp'],
                ]),
                'order' => 4,
            ],

            // ==================== SECTION: TIMELINE ====================
            [
                'page' => 'welcome',
                'section' => 'timeline',
                'key' => 'judul',
                'type' => 'text',
                'value' => 'Alur Bergabung & Pengelolaan Sampah',
                'order' => 1,
            ],
            [
                'page' => 'welcome',
                'section' => 'timeline',
                'key' => 'deskripsi',
                'type' => 'text',
                'value' => 'Langkah sederhana dari pemilahan sampah rumah tangga hingga menjadi saldo tabungan dan produk berdaya guna.',
                'order' => 2,
            ],
            [
                'page' => 'welcome',
                'section' => 'timeline',
                'key' => 'daftar',
                'type' => 'repeater',
                'value' => json_encode([
                    [
                        'nomor' => '01',
                        'fase' => 'Pendaftaran & Pemilahan',
                        'judul' => 'Daftar Anggota & Pilah Sampah',
                        'deskripsi' => 'Daftar akun di koperasi, kemudian pilah sampah dari rumah menjadi organik dan anorganik (plastik, kertas, logam).',
                    ],
                    [
                        'nomor' => '02',
                        'fase' => 'Penimbangan & Pengangkutan',
                        'judul' => 'Setor ke Bank Sampah / Jemput Petugas',
                        'deskripsi' => 'Petugas logistik menimbang sampah menggunakan timbangan digital akurat di lokasi posko atau penjemputan terjadwal.',
                    ],
                    [
                        'nomor' => '03',
                        'fase' => 'Pencatatan Real-time',
                        'judul' => 'Saldo Tabungan Otomatis Masuk',
                        'deskripsi' => 'Hasil penimbangan langsung tercatat secara digital di sistem dan menambah saldo dompet kas anggota.',
                    ],
                    [
                        'nomor' => '04',
                        'fase' => 'Pemberdayaan & Manfaat',
                        'judul' => 'Pencairan Kas & Manfaat SHU',
                        'deskripsi' => 'Saldo dapat ditarik tunai sewaktu-waktu atau dibelanjakan produk pupuk/kebutuhan koperasi serta dividen tahunan SHU.',
                    ],
                ]),
                'order' => 3,
            ],

            // ==================== SECTION: INSTAGRAM / SOSIAL ====================
            [
                'page' => 'welcome',
                'section' => 'instagram',
                'key' => 'judul',
                'type' => 'text',
                'value' => 'Ikuti Aktivitas Kami di Instagram',
                'order' => 1,
            ],
            [
                'page' => 'welcome',
                'section' => 'instagram',
                'key' => 'deskripsi',
                'type' => 'text',
                'value' => 'Pantau dokumentasi kegiatan lapangan, panen maggot, pelatihan daur ulang, dan update pasar sampah melalui media sosial resmi kami.',
                'order' => 2,
            ],
            [
                'page' => 'welcome',
                'section' => 'instagram',
                'key' => 'posts',
                'type' => 'repeater',
                'value' => json_encode([
                    ['url' => 'https://www.instagram.com/p/sample1', 'thumbnail' => 'welcome/ig_post_1.webp', 'caption' => 'Pelatihan pembuatan pupuk organik padat bersama ibu-ibu PKK.'],
                    ['url' => 'https://www.instagram.com/p/sample2', 'thumbnail' => 'welcome/ig_post_2.webp', 'caption' => 'Penimbangan sampah rutin unit pasar minggu ini mencapai rekor 5 ton.'],
                    ['url' => 'https://www.instagram.com/p/sample3', 'thumbnail' => 'welcome/ig_post_3.webp', 'caption' => 'Panen maggot BSF protein tinggi untuk pakan lele anggota koperasi.'],
                ]),
                'order' => 3,
            ],

            // ==================== SECTION: LAYANAN / SERVICES ====================
            [
                'page' => 'welcome',
                'section' => 'services',
                'key' => 'judul',
                'type' => 'text',
                'value' => 'Layanan Utama Koperasi',
                'order' => 1,
            ],
            [
                'page' => 'welcome',
                'section' => 'services',
                'key' => 'deskripsi',
                'type' => 'text',
                'value' => 'Tiga pilar layanan utama untuk mewujudkan ekosistem lingkungan yang bersih dan bernilai finansial nyata.',
                'order' => 2,
            ],
            [
                'page' => 'welcome',
                'section' => 'services',
                'key' => 'daftar_layanan',
                'type' => 'repeater',
                'value' => json_encode([
                    [
                        'icon' => 'bank-note',
                        'nama' => 'Bank Sampah Digital',
                        'keterangan' => 'Tabungan sampah dengan pencatatan transparan dan konversi harga pasar harian terkini.',
                    ],
                    [
                        'icon' => 'truck',
                        'nama' => 'Logistik & Penjemputan Rutin',
                        'keterangan' => 'Armada angkut terjadwal melayani pemukiman warga, sentra UMKM, dan pasar tradisional.',
                    ],
                    [
                        'icon' => 'shopping-cart',
                        'nama' => 'Katalog Produk & Daur Ulang',
                        'keterangan' => 'Penyediaan pupuk kompos, pupuk cair, maggot BSF, dan kerajinan daur ulang siap kirim ke seluruh kota.',
                    ],
                ]),
                'order' => 3,
            ],
        ];

        foreach ($contents as $item) {
            ComproContent::updateOrCreate(
                [
                    'page' => $item['page'],
                    'section' => $item['section'],
                    'key' => $item['key'],
                ],
                $item
            );
        }
    }
}
