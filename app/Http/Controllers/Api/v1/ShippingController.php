<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {}

    /**
     * POST /api/v1/shipping/quotes
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'province' => 'required|string',
            'city' => 'required|string',
            'district' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user('sanctum');

        $result = $this->checkoutService->calculatePreview(
            $validated['items'],
            $validated['province'],
            $validated['city'],
            $validated['district'] ?? null,
            $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Estimasi ongkos kirim berhasil dihitung.',
            'data' => [
                'total_weight_gram' => $result['total_weight_gram'],
                'billable_weight_kg' => $result['billable_weight_kg'],
                'rate_per_kg' => $result['rate_per_kg'],
                'shipping_fee' => $result['shipping_fee'],
                'estimated_days' => $result['estimated_days'],
            ],
        ]);
    }

    /**
     * Admin: GET /api/v1/admin/shipping/rates
     */
    public function indexRates(): JsonResponse
    {
        $rates = ShippingRate::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar tarif pengiriman berhasil dimuat.',
            'data' => $rates,
        ]);
    }

    /**
     * Admin: POST /api/v1/admin/shipping/rates
     */
    public function storeRate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'district' => 'nullable|string|max:100',
            'rate_per_kg' => 'required|integer|min:0',
            'estimated_days_min' => 'required|integer|min:1',
            'estimated_days_max' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $rate = ShippingRate::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tarif pengiriman berhasil ditambahkan.',
            'data' => $rate,
        ], 201);
    }
}
