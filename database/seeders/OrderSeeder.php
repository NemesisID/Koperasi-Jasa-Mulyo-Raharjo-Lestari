<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $product1 = Product::where('sku', 'PRD-ORG-001')->first();
        $product2 = Product::where('sku', 'PRD-MAG-001')->first();
        $userWarga = User::where('username', 'warga')->first();

        // 1. Order Contoh 1: ORD-20261019-ABCDEF (Status: pending / menunggu pembayaran)
        // Cocok digunakan untuk test endpoint GET /api/v1/orders/ORD-20261019-ABCDEF & invoice
        $orderPending = Order::firstOrCreate(
            ['order_number' => 'ORD-20261019-ABCDEF'],
            [
                'idempotency_key' => 'idemp-seed-pending-01',
                'user_id' => $userWarga?->id,
                'customer_name' => 'Budi Santoso',
                'customer_phone' => '081298765432',
                'customer_email' => 'budisantoso@example.com',
                'shipping_province' => 'Jawa Timur',
                'shipping_city' => 'Malang',
                'shipping_district' => 'Klojen',
                'shipping_postal_code' => '65111',
                'shipping_address_detail' => 'Jl. Ijen No. 12, Kel. Oro-oro Dowo',
                'total_weight_gram' => 5500,
                'billable_weight_kg' => 6,
                'shipping_fee' => 60000,
                'subtotal' => 80000,
                'grand_total' => 140000,
                'order_status' => 'pending',
                'payment_status' => 'pending_payment',
                'payment_method' => 'manual_transfer',
                'payment_proof' => null,
                'paid_at' => null,
                'tracking_number' => null,
                'notes' => 'Mohon dikirim pada jam kerja kantor.',
            ]
        );

        // Items untuk order 1
        OrderItem::firstOrCreate(
            [
                'order_id' => $orderPending->id,
                'product_sku' => $product1?->sku ?? 'PRD-ORG-001',
            ],
            [
                'product_id' => $product1?->id,
                'product_name' => $product1?->name ?? 'Pupuk Kompos Premium 5kg',
                'price_tier' => 'retail',
                'unit_price' => 35000,
                'unit_weight_gram' => 5000,
                'quantity' => 1,
                'subtotal' => 35000,
            ]
        );

        OrderItem::firstOrCreate(
            [
                'order_id' => $orderPending->id,
                'product_sku' => $product2?->sku ?? 'PRD-MAG-001',
            ],
            [
                'product_id' => $product2?->id,
                'product_name' => $product2?->name ?? 'Maggot BSF Kering 500g',
                'price_tier' => 'retail',
                'unit_price' => 45000,
                'unit_weight_gram' => 500,
                'quantity' => 1,
                'subtotal' => 45000,
            ]
        );

        OrderStatusHistory::firstOrCreate(
            [
                'order_id' => $orderPending->id,
                'to_status' => 'pending',
            ],
            [
                'from_status' => null,
                'actor_type' => 'customer',
                'actor_id' => $userWarga?->id,
                'notes' => 'Pesanan berhasil dibuat oleh pelanggan.',
            ]
        );

        // 2. Order Contoh 2: ORD-20261019-XYZ789 (Status: processing / sudah bayar lunas)
        $orderPaid = Order::firstOrCreate(
            ['order_number' => 'ORD-20261019-XYZ789'],
            [
                'idempotency_key' => 'idemp-seed-paid-02',
                'user_id' => null, // Guest checkout
                'customer_name' => 'Siti Aminah',
                'customer_phone' => '085712349999',
                'customer_email' => 'sitiaminah@example.com',
                'shipping_province' => 'Jawa Timur',
                'shipping_city' => 'Surabaya',
                'shipping_district' => 'Wonokromo',
                'shipping_postal_code' => '60243',
                'shipping_address_detail' => 'Jl. Darmo No. 88',
                'total_weight_gram' => 5000,
                'billable_weight_kg' => 5,
                'shipping_fee' => 55000,
                'subtotal' => 35000,
                'grand_total' => 90000,
                'order_status' => 'processing',
                'payment_status' => 'paid',
                'payment_method' => 'manual_transfer',
                'payment_proof' => 'payment_proofs/sample-transfer.jpg',
                'paid_at' => now()->subHours(2),
                'tracking_number' => null,
                'notes' => 'Tolong dibungkus bubble wrap tambahan.',
            ]
        );

        OrderItem::firstOrCreate(
            [
                'order_id' => $orderPaid->id,
                'product_sku' => $product1?->sku ?? 'PRD-ORG-001',
            ],
            [
                'product_id' => $product1?->id,
                'product_name' => $product1?->name ?? 'Pupuk Kompos Premium 5kg',
                'price_tier' => 'retail',
                'unit_price' => 35000,
                'unit_weight_gram' => 5000,
                'quantity' => 1,
                'subtotal' => 35000,
            ]
        );

        OrderStatusHistory::firstOrCreate(
            [
                'order_id' => $orderPaid->id,
                'to_status' => 'processing',
            ],
            [
                'from_status' => 'pending',
                'actor_type' => 'admin',
                'actor_id' => 1,
                'notes' => 'Pembayaran telah diverifikasi oleh admin koperasi.',
            ]
        );
    }
}
