<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoriesData = [
            [
                'name' => 'Olahan Sampah Organik',
                'slug' => 'olahan-sampah-organik',
                'description' => 'Produk hasil daur ulang limbah organik seperti kompos, biourine, dan pupuk cair bermutu tinggi.',
                'image' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Pakan Ternak & Maggot',
                'slug' => 'pakan-ternak-maggot',
                'description' => 'Hasil budidaya larva BSF / Black Soldier Fly dengan nutrisi dan protein tinggi untuk pakan ternak dan ikan.',
                'image' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Kerajinan Daur Ulang',
                'slug' => 'kerajinan-daur-ulang',
                'description' => 'Produk kreatif dan bernilai ekonomi tinggi dari bahan sampah anorganik (plastik, kertas, botol).',
                'image' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Perlengkapan & Wadah Sampah',
                'slug' => 'perlengkapan-wadah-sampah',
                'description' => 'Peralatan pemilah sampah, tong komposter, dan trash bag ramah lingkungan binaan koperasi.',
                'image' => null,
                'is_active' => true,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $catData) {
            $categories[$catData['slug']] = ProductCategory::firstOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );
        }

        $productsData = [
            [
                'category_slug' => 'olahan-sampah-organik',
                'sku' => 'PRD-ORG-001',
                'name' => 'Pupuk Kompos Premium 5kg',
                'slug' => 'pupuk-kompos-premium-5kg',
                'description' => 'Pupuk kompos organik hasil fermentasi sampah dedaunan dan pasar pilihan. Mengandung hara makro mikro lengkap untuk menyuburkan tanah dan tanaman buah/hias.',
                'weight_gram' => 5000,
                'stock_qty' => 120,
                'image' => null,
                'is_active' => true,
                'is_featured' => true,
                'prices' => [
                    'retail' => 35000,
                    'distributor' => 28000,
                ],
            ],
            [
                'category_slug' => 'olahan-sampah-organik',
                'sku' => 'PRD-ORG-002',
                'name' => 'Pupuk Kompos Premium 1.5kg',
                'slug' => 'pupuk-kompos-premium-1-5kg',
                'description' => 'Kemasan praktis pupuk kompos organik matang siap pakai untuk tanaman pot, hidroponik dasar, dan sayuran pekarangan.',
                'weight_gram' => 1500,
                'stock_qty' => 200,
                'image' => null,
                'is_active' => true,
                'is_featured' => false,
                'prices' => [
                    'retail' => 15000,
                    'distributor' => 11000,
                ],
            ],
            [
                'category_slug' => 'olahan-sampah-organik',
                'sku' => 'PRD-POC-001',
                'name' => 'Pupuk Organik Cair (POC) Biourine 1 Liter',
                'slug' => 'pupuk-organik-cair-poc-biourine-1-liter',
                'description' => 'POC hasil peragian mikroorganisme alami diperkaya nutrisi pemacu pertumbuhan akar, tunas, dan bunga.',
                'weight_gram' => 1100,
                'stock_qty' => 85,
                'image' => null,
                'is_active' => true,
                'is_featured' => true,
                'prices' => [
                    'retail' => 25000,
                    'distributor' => 19000,
                ],
            ],
            [
                'category_slug' => 'pakan-ternak-maggot',
                'sku' => 'PRD-MAG-001',
                'name' => 'Maggot BSF Kering 500g',
                'slug' => 'maggot-bsf-kering-500g',
                'description' => 'Maggot BSF oven kering dengan kadar protein hingga 45%. Sangat disukai ikan koi, lele, burung berkicau, dan unggas.',
                'weight_gram' => 500,
                'stock_qty' => 90,
                'image' => null,
                'is_active' => true,
                'is_featured' => true,
                'prices' => [
                    'retail' => 45000,
                    'distributor' => 36000,
                ],
            ],
            [
                'category_slug' => 'pakan-ternak-maggot',
                'sku' => 'PRD-MAG-002',
                'name' => 'Tepung Maggot Super Protein 1kg',
                'slug' => 'tepung-maggot-super-protein-1kg',
                'description' => 'Tepung larva BSF olahan halus untuk campuran pakan konsentrat peternak ayam dan pelet ikan.',
                'weight_gram' => 1000,
                'stock_qty' => 60,
                'image' => null,
                'is_active' => true,
                'is_featured' => false,
                'prices' => [
                    'retail' => 65000,
                    'distributor' => 52000,
                ],
            ],
            [
                'category_slug' => 'kerajinan-daur-ulang',
                'sku' => 'PRD-KRN-001',
                'name' => 'Tas Anyaman Daur Ulang Plastik Eco-Tote',
                'slug' => 'tas-anyaman-daur-ulang-plastik-eco-tote',
                'description' => 'Tas belanja stylish tahan air buatan kelompok ibu-ibu PKK nasabah bank sampah, terbuat dari anyaman sachet dan kresek higienis.',
                'weight_gram' => 350,
                'stock_qty' => 40,
                'image' => null,
                'is_active' => true,
                'is_featured' => true,
                'prices' => [
                    'retail' => 40000,
                    'distributor' => 30000,
                ],
            ],
            [
                'category_slug' => 'kerajinan-daur-ulang',
                'sku' => 'PRD-KRN-002',
                'name' => 'Pot Bunga Estetik Daur Ulang Galon Mini',
                'slug' => 'pot-bunga-estetik-daur-ulang-galon-mini',
                'description' => 'Pot tanaman hias meja dengan lukisan tangan dekoratif memanfaatkan limbah botol dan galon air.',
                'weight_gram' => 450,
                'stock_qty' => 50,
                'image' => null,
                'is_active' => true,
                'is_featured' => false,
                'prices' => [
                    'retail' => 20000,
                    'distributor' => 15000,
                ],
            ],
            [
                'category_slug' => 'perlengkapan-wadah-sampah',
                'sku' => 'PRD-EQP-001',
                'name' => 'Ember Komposter Rumah Tangga 20L',
                'slug' => 'ember-komposter-rumah-tangga-20l',
                'description' => 'Wadah komposter aerobik skala rumah tangga dilengkapi kran panen lindi / POC dan saringan sirkulasi udara.',
                'weight_gram' => 2200,
                'stock_qty' => 30,
                'image' => null,
                'is_active' => true,
                'is_featured' => true,
                'prices' => [
                    'retail' => 110000,
                    'distributor' => 88000,
                ],
            ],
        ];

        foreach ($productsData as $item) {
            $category = $categories[$item['category_slug']] ?? null;

            $product = Product::firstOrCreate(
                ['sku' => $item['sku']],
                [
                    'category_id' => $category?->id,
                    'name' => $item['name'],
                    'slug' => $item['slug'] ?? Str::slug($item['name']),
                    'description' => $item['description'],
                    'weight_gram' => $item['weight_gram'],
                    'stock_qty' => $item['stock_qty'],
                    'image' => $item['image'],
                    'is_active' => $item['is_active'],
                    'is_featured' => $item['is_featured'],
                ]
            );

            // Seed harga (retail & distributor)
            foreach ($item['prices'] as $tier => $price) {
                ProductPrice::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'tier' => $tier,
                    ],
                    [
                        'price' => $price,
                    ]
                );
            }
        }
    }
}
