<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    private function getOrCreateCart(Request $request): Cart
    {
        $user = $request->user('sanctum');
        if ($user) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        }

        $sessionToken = $request->header('X-Cart-Session') ?: $request->input('session_token');
        if (! $sessionToken) {
            $sessionToken = 'cart_' . Str::random(32);
        }

        return Cart::firstOrCreate(['session_token' => $sessionToken]);
    }

    /**
     * GET /api/v1/cart
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $user = $request->user('sanctum');
        $tier = ($user && $user->role === 'distributor' && $user->is_distributor_verified) ? 'distributor' : 'retail';

        $items = CartItem::with(['product.prices'])
            ->where('cart_id', $cart->id)
            ->get();

        $subtotal = 0;
        $totalWeightGram = 0;

        $formattedItems = $items->map(function ($item) use (&$subtotal, &$totalWeightGram, $tier) {
            $product = $item->product;
            $unitPrice = $product ? $product->getPriceForTier($tier) : 0;
            $itemSubtotal = $unitPrice * $item->quantity;
            $itemWeight = ($product ? $product->weight_gram : 0) * $item->quantity;

            $subtotal += $itemSubtotal;
            $totalWeightGram += $itemWeight;

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $product?->name,
                'product_image' => $product?->image,
                'weight_gram' => $product?->weight_gram,
                'unit_price' => $unitPrice,
                'quantity' => $item->quantity,
                'subtotal' => $itemSubtotal,
                'available_stock' => $product?->stock_qty,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Isi keranjang berhasil dimuat.',
            'data' => [
                'cart_id' => $cart->id,
                'session_token' => $cart->session_token,
                'items' => $formattedItems,
                'subtotal' => $subtotal,
                'total_weight_gram' => $totalWeightGram,
            ],
        ]);
    }

    /**
     * POST /api/v1/cart/items
     */
    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'session_token' => 'nullable|string',
        ]);

        $cart = $this->getOrCreateCart($request);
        $product = Product::findOrFail($validated['product_id']);

        if (! $product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Produk ini sedang tidak aktif.',
            ], 422);
        }

        $cartItem = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $newQty = ($cartItem->exists ? $cartItem->quantity : 0) + $validated['quantity'];

        if ($product->stock_qty < $newQty) {
            return response()->json([
                'success' => false,
                'message' => "Stok produk tidak mencukupi (tersisa: {$product->stock_qty}).",
            ], 422);
        }

        $cartItem->quantity = $newQty;
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan ke keranjang.',
            'data' => [
                'cart_id' => $cart->id,
                'session_token' => $cart->session_token,
                'item' => $cartItem,
            ],
        ]);
    }

    /**
     * PUT /api/v1/cart/items/{id}
     */
    public function updateItem(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = CartItem::with('product')->findOrFail($id);

        if ($cartItem->product->stock_qty < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => "Stok produk tidak mencukupi (tersisa: {$cartItem->product->stock_qty}).",
            ], 422);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);

        return response()->json([
            'success' => true,
            'message' => 'Jumlah produk di keranjang berhasil diperbarui.',
            'data' => $cartItem,
        ]);
    }

    /**
     * DELETE /api/v1/cart/items/{id}
     */
    public function removeItem(int $id): JsonResponse
    {
        CartItem::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk dihapus dari keranjang.',
        ]);
    }
}
