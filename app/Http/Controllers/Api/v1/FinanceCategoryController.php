<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreFinanceCategoryRequest;
use App\Http\Resources\Finance\FinanceCategoryResource;
use App\Models\FinanceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceCategoryController extends Controller
{
    /**
     * GET /api/v1/finance-categories
     */
    public function index(Request $request): JsonResponse
    {
        $categories = FinanceCategory::query()
            ->when($request->input('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->input('group_type'), fn ($query, $group) => $query->where('group_type', $group))
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kategori keuangan berhasil dimuat.',
            'data' => FinanceCategoryResource::collection($categories),
        ]);
    }

    /**
     * POST /api/v1/finance-categories
     */
    public function store(StoreFinanceCategoryRequest $request): JsonResponse
    {
        $category = FinanceCategory::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kategori keuangan berhasil dibuat.',
            'data' => new FinanceCategoryResource($category),
        ], 201);
    }
}
