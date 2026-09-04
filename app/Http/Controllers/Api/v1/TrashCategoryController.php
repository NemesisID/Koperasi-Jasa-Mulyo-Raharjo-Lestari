<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trash\StoreTrashCategoryRequest;
use App\Http\Requests\Trash\UpdateTrashCategoryRequest;
use App\Http\Requests\Trash\UpdateTrashPriceRequest;
use App\Http\Resources\Trash\PriceHistoryResource;
use App\Http\Resources\Trash\TrashCategoryResource;
use App\Services\TrashCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrashCategoryController extends Controller
{
    public function __construct(
        private readonly TrashCategoryService $trashCategoryService,
    ) {}

    /**
     * GET /api/v1/trash-categories (publik)
     */
    public function index(Request $request): JsonResponse
    {
        $categories = $this->trashCategoryService->getCategories(
            $request->only(['type', 'is_active']),
        );

        return response()->json([
            'success' => true,
            'message' => 'Katalog sampah berhasil dimuat.',
            'data' => TrashCategoryResource::collection($categories),
        ]);
    }

    /**
     * GET /api/v1/trash-categories/board (publik)
     */
    public function board(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Papan info harga berhasil dimuat.',
            'data' => $this->trashCategoryService->getPriceBoard(),
        ]);
    }

    /**
     * POST /api/v1/trash-categories
     */
    public function store(StoreTrashCategoryRequest $request): JsonResponse
    {
        $category = $this->trashCategoryService->createCategory($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kategori sampah berhasil dibuat.',
            'data' => new TrashCategoryResource($category),
        ], 201);
    }

    /**
     * GET /api/v1/trash-categories/{id}
     */
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail kategori sampah berhasil dimuat.',
            'data' => new TrashCategoryResource($this->trashCategoryService->getCategory($id)),
        ]);
    }

    /**
     * PUT /api/v1/trash-categories/{id}
     */
    public function update(UpdateTrashCategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->trashCategoryService->updateCategory($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kategori sampah berhasil diperbarui.',
            'data' => new TrashCategoryResource($category),
        ]);
    }

    /**
     * PATCH /api/v1/trash-categories/{id}/price
     */
    public function updatePrice(UpdateTrashPriceRequest $request, int $id): JsonResponse
    {
        $category = $this->trashCategoryService->updateDailyPrice(
            $id,
            $request->validated(),
            $request->user(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Harga harian berhasil diperbarui dan tercatat di audit log.',
            'data' => new TrashCategoryResource($category),
        ]);
    }

    /**
     * GET /api/v1/trash-categories/{id}/price-history
     */
    public function priceHistory(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Riwayat perubahan harga berhasil dimuat.',
            'data' => PriceHistoryResource::collection($this->trashCategoryService->getPriceHistory($id)),
        ]);
    }
}
