<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\PriceAudit;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * GET /api/v1/products (Publik / User)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();
        $tier = ($user && $user->role === 'distributor' && $user->is_distributor_verified) ? 'distributor' : 'retail';

        $query = Product::with(['category', 'prices'])->where('is_active', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->latest()->paginate($request->input('per_page', 15));

        $products->getCollection()->transform(function ($product) use ($tier) {
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
                'weight_gram' => $product->weight_gram,
                'stock_qty' => $product->stock_qty,
                'image' => $product->image,
                'price' => $product->getPriceForTier($tier),
                'price_tier' => $tier,
                'is_featured' => $product->is_featured,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Katalog produk berhasil dimuat.',
            'data' => $products,
        ]);
    }

    /**
     * GET /api/v1/products/{slug} (Publik)
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $user = $request->user('sanctum');
        $tier = ($user && $user->role === 'distributor' && $user->is_distributor_verified) ? 'distributor' : 'retail';

        $product = Product::with(['category', 'prices'])->where('slug', $slug)->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil dimuat.',
            'data' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
                'weight_gram' => $product->weight_gram,
                'stock_qty' => $product->stock_qty,
                'image' => $product->image,
                'price' => $product->getPriceForTier($tier),
                'price_tier' => $tier,
                'is_featured' => $product->is_featured,
            ],
        ]);
    }

    /**
     * Admin: POST /api/v1/admin/products
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:product_categories,id',
            'sku' => 'required|string|unique:products,sku',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight_gram' => 'required|integer|min:0',
            'stock_qty' => 'required|integer|min:0',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'retail_price' => 'required|integer|min:0',
            'distributor_price' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);

        $product = Product::create($validated);

        // Set initial prices
        ProductPrice::create([
            'product_id' => $product->id,
            'tier' => 'retail',
            'price' => $validated['retail_price'],
        ]);

        if (! empty($validated['distributor_price'])) {
            ProductPrice::create([
                'product_id' => $product->id,
                'tier' => 'distributor',
                'price' => $validated['distributor_price'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan.',
            'data' => $product->load(['category', 'prices']),
        ], 201);
    }

    /**
     * Admin: PUT /api/v1/admin/products/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'nullable|exists:product_categories,id',
            'sku' => "required|string|unique:products,sku,{$id}",
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight_gram' => 'required|integer|min:0',
            'stock_qty' => 'required|integer|min:0',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui.',
            'data' => $product->load(['category', 'prices']),
        ]);
    }

    /**
     * Admin: PUT /api/v1/admin/products/{id}/prices
     */
    public function updatePrices(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $admin = $request->user();

        $validated = $request->validate([
            'retail_price' => 'required|integer|min:0',
            'distributor_price' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        // Update retail
        $retail = ProductPrice::firstOrNew(['product_id' => $product->id, 'tier' => 'retail']);
        $oldRetail = $retail->price;
        $retail->price = $validated['retail_price'];
        $retail->save();

        if ($oldRetail !== $validated['retail_price']) {
            PriceAudit::create([
                'product_id' => $product->id,
                'tier' => 'retail',
                'old_price' => $oldRetail,
                'new_price' => $validated['retail_price'],
                'admin_id' => $admin?->id,
                'notes' => $validated['notes'] ?? 'Update harga retail',
            ]);
        }

        // Update distributor
        if (isset($validated['distributor_price'])) {
            $dist = ProductPrice::firstOrNew(['product_id' => $product->id, 'tier' => 'distributor']);
            $oldDist = $dist->price;
            $dist->price = $validated['distributor_price'];
            $dist->save();

            if ($oldDist !== $validated['distributor_price']) {
                PriceAudit::create([
                    'product_id' => $product->id,
                    'tier' => 'distributor',
                    'old_price' => $oldDist,
                    'new_price' => $validated['distributor_price'],
                    'admin_id' => $admin?->id,
                    'notes' => $validated['notes'] ?? 'Update harga distributor',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Harga produk berhasil diperbarui dan dicatat dalam audit histori.',
            'data' => $product->load('prices'),
        ]);
    }
}
