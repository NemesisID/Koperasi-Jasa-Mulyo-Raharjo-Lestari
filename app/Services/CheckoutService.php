<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /**
     * Hitung ringkasan kalkulasi sebelum checkout (Preview)
     */
    public function calculatePreview(array $items, string $province, string $city, ?string $district = null, ?User $user = null): array
    {
        $tier = ($user && $user->role === 'distributor' && $user->is_distributor_verified) ? 'distributor' : 'retail';

        $subtotal = 0;
        $totalWeightGram = 0;
        $evaluatedItems = [];

        foreach ($items as $item) {
            $product = Product::with('prices')->findOrFail($item['product_id']);

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'items' => ["Produk {$product->name} saat ini sedang tidak aktif."],
                ]);
            }

            if ($product->stock_qty < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => ["Stok produk {$product->name} tidak mencukupi (tersedia: {$product->stock_qty})."],
                ]);
            }

            $unitPrice = $product->getPriceForTier($tier);
            if ($unitPrice === null) {
                throw ValidationException::withMessages([
                    'items' => ["Harga untuk produk {$product->name} belum dikonfigurasi."],
                ]);
            }

            $itemSubtotal = $unitPrice * $item['quantity'];
            $itemWeight = (int) $product->weight_gram * $item['quantity'];

            $subtotal += $itemSubtotal;
            $totalWeightGram += $itemWeight;

            $evaluatedItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'unit_price' => $unitPrice,
                'unit_weight_gram' => (int) $product->weight_gram,
                'quantity' => (int) $item['quantity'],
                'subtotal' => $itemSubtotal,
                'price_tier' => $tier,
            ];
        }

        // Hitung Berat Tagihan (kg) = pembulatan ke atas dari total berat / 1.000 gram
        $billableWeightKg = max(1, (int) ceil($totalWeightGram / 1000));

        // Hitung Ongkir
        $rate = ShippingRate::findRate($province, $city, $district);
        $ratePerKg = $rate ? (int) $rate->rate_per_kg : 15000; // default flat rate jika tidak terdaftar
        $shippingFee = $billableWeightKg * $ratePerKg;

        $grandTotal = $subtotal + $shippingFee;

        return [
            'tier' => $tier,
            'items' => $evaluatedItems,
            'subtotal' => $subtotal,
            'total_weight_gram' => $totalWeightGram,
            'billable_weight_kg' => $billableWeightKg,
            'rate_per_kg' => $ratePerKg,
            'shipping_fee' => $shippingFee,
            'grand_total' => $grandTotal,
            'shipping_rate_id' => $rate?->id,
            'estimated_days' => $rate ? "{$rate->estimated_days_min} - {$rate->estimated_days_max} hari" : '2 - 4 hari',
        ];
    }

    /**
     * Buat Pesanan (Order) dengan DB Transaction, Idempotency, dan Pengurangan Stok
     */
    public function createOrder(array $data, ?User $user = null): Order
    {
        $idempotencyKey = $data['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existing = Order::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($data, $user, $idempotencyKey) {
            $preview = $this->calculatePreview(
                $data['items'],
                $data['shipping_province'],
                $data['shipping_city'],
                $data['shipping_district'] ?? null,
                $user
            );

            // Validasi & Potong Stok secara atomik dengan lock
            foreach ($preview['items'] as $itemData) {
                $product = Product::where('id', $itemData['product_id'])->lockForUpdate()->first();
                if ($product->stock_qty < $itemData['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Stok produk {$product->name} tidak mencukupi saat proses checkout."],
                    ]);
                }
                $product->decrement('stock_qty', $itemData['quantity']);
            }

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'idempotency_key' => $idempotencyKey,
                'user_id' => $user?->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'shipping_province' => $data['shipping_province'],
                'shipping_city' => $data['shipping_city'],
                'shipping_district' => $data['shipping_district'],
                'shipping_postal_code' => $data['shipping_postal_code'] ?? null,
                'shipping_address_detail' => $data['shipping_address_detail'],
                'total_weight_gram' => $preview['total_weight_gram'],
                'billable_weight_kg' => $preview['billable_weight_kg'],
                'shipping_fee' => $preview['shipping_fee'],
                'subtotal' => $preview['subtotal'],
                'grand_total' => $preview['grand_total'],
                'order_status' => 'pending',
                'payment_status' => 'pending_payment',
                'payment_method' => $data['payment_method'] ?? 'manual_transfer',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($preview['items'] as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'product_sku' => $itemData['product_sku'],
                    'product_name' => $itemData['product_name'],
                    'price_tier' => $itemData['price_tier'],
                    'unit_price' => $itemData['unit_price'],
                    'unit_weight_gram' => $itemData['unit_weight_gram'],
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $itemData['subtotal'],
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'pending',
                'actor_type' => $user ? 'customer' : 'guest',
                'actor_id' => $user?->id,
                'notes' => 'Pesanan berhasil dibuat, menunggu pembayaran.',
            ]);

            // Bersihkan keranjang jika session_token / cart_id disediakan
            if (! empty($data['cart_id'])) {
                Cart::where('id', $data['cart_id'])->delete();
            }

            return $order->load(['items', 'statusHistories']);
        });
    }
}
