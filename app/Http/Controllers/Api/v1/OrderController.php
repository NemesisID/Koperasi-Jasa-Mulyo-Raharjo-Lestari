<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {}

    /**
     * POST /api/v1/checkout/preview
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_province' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_district' => 'nullable|string',
        ]);

        $user = $request->user('sanctum') ?? $request->user();

        $preview = $this->checkoutService->calculatePreview(
            $validated['items'],
            $validated['shipping_province'],
            $validated['shipping_city'],
            $validated['shipping_district'] ?? null,
            $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan preview checkout berhasil dihitung.',
            'data' => $preview,
        ]);
    }

    /**
     * POST /api/v1/orders (Create Order)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'shipping_province' => 'required|string|max:100',
            'shipping_city' => 'required|string|max:100',
            'shipping_district' => 'required|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_address_detail' => 'required|string',
            'payment_method' => 'nullable|string|in:manual_transfer,qris',
            'idempotency_key' => 'nullable|string|max:100',
            'cart_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user('sanctum') ?? $request->user();
        $order = $this->checkoutService->createOrder($validated, $user);

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibuat.',
            'data' => $order,
        ], 201);
    }

    /**
     * GET /api/v1/orders/{id}
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        $order = Order::with(['items', 'statusHistories'])
            ->where(function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('id', $id)->orWhere('order_number', $id);
                } else {
                    $q->where('order_number', $id);
                }
            })->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil dimuat.',
            'data' => $order,
        ]);
    }

    /**
     * GET /api/v1/orders/{id}/invoice (Nota Digital Payload JSON)
     */
    public function invoice(int|string $id): JsonResponse
    {
        $order = Order::with(['items', 'statusHistories'])
            ->where(function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('id', $id)->orWhere('order_number', $id);
                } else {
                    $q->where('order_number', $id);
                }
            })->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Nota tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nota digital pesanan berhasil dimuat.',
            'data' => [
                'invoice_number' => 'INV-' . $order->order_number,
                'order_number' => $order->order_number,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'customer' => [
                    'name' => $order->customer_name,
                    'phone' => $order->customer_phone,
                    'email' => $order->customer_email,
                ],
                'shipping_destination' => [
                    'province' => $order->shipping_province,
                    'city' => $order->shipping_city,
                    'district' => $order->shipping_district,
                    'postal_code' => $order->shipping_postal_code,
                    'address_detail' => $order->shipping_address_detail,
                ],
                'items' => $order->items->map(fn ($item) => [
                    'sku' => $item->product_sku,
                    'name' => $item->product_name,
                    'tier' => $item->price_tier,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'weight_gram' => $item->unit_weight_gram,
                    'subtotal' => $item->subtotal,
                ]),
                'calculation' => [
                    'total_weight_gram' => $order->total_weight_gram,
                    'billable_weight_kg' => $order->billable_weight_kg,
                    'shipping_fee' => $order->shipping_fee,
                    'subtotal' => $order->subtotal,
                    'grand_total' => $order->grand_total,
                ],
                'payment' => [
                    'method' => $order->payment_method,
                    'status' => $order->payment_status,
                    'paid_at' => $order->paid_at?->format('Y-m-d H:i:s'),
                    'payment_proof' => $order->payment_proof ? asset('storage/' . $order->payment_proof) : null,
                ],
                'fulfillment' => [
                    'status' => $order->order_status,
                    'tracking_number' => $order->tracking_number,
                ],
            ],
        ]);
    }

    /**
     * POST /api/v1/orders/{id}/payment-proof (Upload Bukti Bayar)
     */
    public function uploadPaymentProof(Request $request, int|string $id): JsonResponse
    {
        $order = Order::where(function ($q) use ($id) {
            if (is_numeric($id)) {
                $q->where('id', $id)->orWhere('order_number', $id);
            } else {
                $q->where('order_number', $id);
            }
        })->firstOrFail();

        $request->validate([
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $path = $request->file('payment_proof')->store('payment_proofs', 'public');
        $order->update([
            'payment_proof' => $path,
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => $order->order_status,
            'to_status' => $order->order_status,
            'actor_type' => 'customer',
            'notes' => 'Pelanggan telah mengunggah bukti pembayaran.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bukti pembayaran berhasil diunggah. Menunggu konfirmasi admin.',
            'data' => [
                'order_number' => $order->order_number,
                'payment_proof_url' => asset('storage/' . $path),
            ],
        ]);
    }

    /**
     * Admin: PATCH /api/v1/admin/orders/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $admin = $request->user();

        $validated = $request->validate([
            'order_status' => 'nullable|in:pending,processing,shipped,completed,cancelled',
            'payment_status' => 'nullable|in:pending_payment,paid,expired,failed',
            'tracking_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $order->order_status;

        if (isset($validated['payment_status']) && $validated['payment_status'] === 'paid' && $order->payment_status !== 'paid') {
            $order->paid_at = now();
        }

        $order->fill($validated);
        $order->save();

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => $oldStatus,
            'to_status' => $order->order_status,
            'actor_type' => 'admin',
            'actor_id' => $admin?->id,
            'notes' => $validated['notes'] ?? 'Status pesanan diperbarui oleh admin.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui.',
            'data' => $order->fresh(['items', 'statusHistories']),
        ]);
    }
}
