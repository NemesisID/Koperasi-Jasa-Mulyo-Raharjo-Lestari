<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberCategoryRequest;
use App\Http\Resources\Member\MemberCategoryResource;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;

class MemberCategoryController extends Controller
{
    public function __construct(
        private readonly MemberService $memberService,
    ) {}

    /**
     * GET /api/v1/member-categories
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar kategori anggota berhasil dimuat.',
            'data' => MemberCategoryResource::collection($this->memberService->listCategories()),
        ]);
    }

    /**
     * POST /api/v1/member-categories
     */
    public function store(StoreMemberCategoryRequest $request): JsonResponse
    {
        $category = $this->memberService->createCategory($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kategori anggota berhasil dibuat.',
            'data' => new MemberCategoryResource($category),
        ], 201);
    }
}
