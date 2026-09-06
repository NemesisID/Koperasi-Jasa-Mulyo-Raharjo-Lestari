<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\StoreLogisticsRouteRequest;
use App\Http\Resources\Logistics\LogisticsRouteResource;
use App\Repositories\Contracts\DetailPengangkutanRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogisticsController extends Controller
{
    public function __construct(
        private readonly DetailPengangkutanRepositoryInterface $pengangkutanRepository,
    ) {}

    /**
     * GET /api/v1/logistics/routes — log ritase armada per wilayah
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->pengangkutanRepository->paginate(
            $request->only(['desa', 'rw', 'rt', 'start_date', 'end_date', 'per_page']),
        );

        return response()->json([
            'success' => true,
            'message' => 'Log ritase pengangkutan berhasil dimuat.',
            'data' => LogisticsRouteResource::collection($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/logistics/routes — catat ritase baru
     */
    public function store(StoreLogisticsRouteRequest $request): JsonResponse
    {
        $route = $this->pengangkutanRepository->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ritase pengangkutan berhasil dicatat.',
            'data' => new LogisticsRouteResource($route->load('user:id,name,username')),
        ], 201);
    }
}
