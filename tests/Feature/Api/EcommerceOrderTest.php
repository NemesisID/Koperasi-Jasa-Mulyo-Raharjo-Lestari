<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcommerceOrderTest extends TestCase
{
    use RefreshDatabase;

    private Product $productA;
    private Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $category = ProductCategory::create([
            'name' => 'Olahan Sampah Organik',
            'slug' => 'olahan-sampah-organik',
        ]);

        // Produk A: 1.500 gram, stok 10
        $this->productA = Product::create([
            'category_id' => $category->id,
            'sku' => 'PRD-ORG-01',
            'name' => 'Pupuk Kompos Premium 1.5kg',
            'slug' => 'pupuk-kompos-premium-1-5kg',
            'weight_gram' => 1500,
            'stock_qty' => 10,
            'is_active' => true,
        ]);

        ProductPrice::create(['product_id' => $this->productA->id, 'tier' => 'retail', 'price' => 45000]);
        ProductPrice::create(['product_id' => $this->productA->id, 'tier' => 'distributor', 'price' => 35000]);

        // Produk B: 500 gram, stok 5
        $this->productB = Product::create([
            'category_id' => $category->id,
            'sku' => 'PRD-MAG-01',
            'name' => 'Maggot Kering 500g',
            'slug' => 'maggot-kering-500g',
            'weight_gram' => 500,
            'stock_qty' => 5,
            'is_active' => true,
        ]);

        ProductPrice::create(['product_id' => $this->productB->id, 'tier' => 'retail', 'price' => 120000]);
        ProductPrice::create(['product_id' => $this->productB->id, 'tier' => 'distributor', 'price' => 100000]);

        // Shipping Rate: Jawa Timur, Malang = 12.000 / kg
        ShippingRate::create([
            'province' => 'Jawa Timur',
            'city' => 'Malang',
            'rate_per_kg' => 12000,
            'estimated_days_min' => 1,
            'estimated_days_max' => 2,
            'is_active' => true,
        ]);
    }

    public function test_guest_can_calculate_checkout_preview_with_weight_rounding()
    {
        // Simulasi dari PRD Bagian 6:
        // Produk A (2 x 45.000 = 90.000, 2 x 1.500g = 3.000g)
        // Produk B (1 x 120.000 = 120.000, 1 x 500g = 500g)
        // Total berat = 3.500g -> Ceil pembulatan ke atas = 4 kg
        // Ongkir = 4 x 12.000 = 48.000
        // Grand Total = 210.000 + 48.000 = 258.000

        $response = $this->postJson('/api/v1/checkout/preview', [
            'shipping_province' => 'Jawa Timur',
            'shipping_city' => 'Malang',
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 2],
                ['product_id' => $this->productB->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.tier', 'retail')
            ->assertJsonPath('data.subtotal', 210000)
            ->assertJsonPath('data.total_weight_gram', 3500)
            ->assertJsonPath('data.billable_weight_kg', 4)
            ->assertJsonPath('data.shipping_fee', 48000)
            ->assertJsonPath('data.grand_total', 258000);
    }

    public function test_distributor_gets_distributor_tier_price_on_preview()
    {
        $distributor = User::create([
            'name' => 'Mitra Distributor',
            'username' => 'distributor1',
            'email' => 'distributor@koperasi.id',
            'password' => bcrypt('password'),
            'role' => 'distributor',
            'is_distributor_verified' => true,
        ]);

        // Produk A: 2 x 35.000 = 70.000
        // Produk B: 1 x 100.000 = 100.000
        // Subtotal = 170.000
        // Berat = 3.500g -> 4 kg -> Ongkir = 48.000
        // Grand total = 218.000

        $response = $this->actingAs($distributor, 'sanctum')
            ->postJson('/api/v1/checkout/preview', [
                'shipping_province' => 'Jawa Timur',
                'shipping_city' => 'Malang',
                'items' => [
                    ['product_id' => $this->productA->id, 'quantity' => 2],
                    ['product_id' => $this->productB->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.tier', 'distributor')
            ->assertJsonPath('data.subtotal', 170000)
            ->assertJsonPath('data.grand_total', 218000);
    }

    public function test_guest_can_create_order_and_stock_is_decremented_atomically()
    {
        $initialStockA = $this->productA->stock_qty;
        $initialStockB = $this->productB->stock_qty;

        $response = $this->postJson('/api/v1/orders', [
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'customer_email' => 'budi@example.com',
            'shipping_province' => 'Jawa Timur',
            'shipping_city' => 'Malang',
            'shipping_district' => 'Klojen',
            'shipping_postal_code' => '65111',
            'shipping_address_detail' => 'Jl. Ijen No. 12',
            'payment_method' => 'manual_transfer',
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 2],
                ['product_id' => $this->productB->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201);
        $orderId = $response->json('data.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'customer_name' => 'Budi Santoso',
            'grand_total' => 258000,
            'order_status' => 'pending',
            'payment_status' => 'pending_payment',
        ]);

        // Verifikasi pengurangan stok
        $this->assertEquals($initialStockA - 2, $this->productA->fresh()->stock_qty);
        $this->assertEquals($initialStockB - 1, $this->productB->fresh()->stock_qty);

        // Verifikasi snapshot order_items
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_sku' => 'PRD-ORG-01',
            'unit_price' => 45000,
            'quantity' => 2,
            'subtotal' => 90000,
        ]);
    }

    public function test_order_invoice_payload_structure()
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'customer_name' => 'Siti Rahma',
            'customer_phone' => '08987654321',
            'shipping_province' => 'Jawa Timur',
            'shipping_city' => 'Malang',
            'shipping_district' => 'Lowokwaru',
            'shipping_address_detail' => 'Jl. Soekarno Hatta No. 9',
            'total_weight_gram' => 1500,
            'billable_weight_kg' => 2,
            'shipping_fee' => 24000,
            'subtotal' => 45000,
            'grand_total' => 69000,
            'order_status' => 'pending',
            'payment_status' => 'pending_payment',
        ]);

        $response = $this->getJson("/api/v1/orders/{$order->id}/invoice");

        $response->assertStatus(200)
            ->assertJsonPath('data.order_number', 'ORD-TEST-001')
            ->assertJsonPath('data.customer.name', 'Siti Rahma')
            ->assertJsonPath('data.calculation.grand_total', 69000);
    }
}
