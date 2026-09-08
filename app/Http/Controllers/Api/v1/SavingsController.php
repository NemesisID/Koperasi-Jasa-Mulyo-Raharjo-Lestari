<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Savings\PaySavingsRequest;
use App\Http\Resources\Savings\SavingsResource;
use App\Services\SavingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavingsController extends Controller
{
    public function __construct(
        private readonly SavingsService $savingsService,
    ) {}

    /**
     * GET /api/v1/savings — rekap simpanan seluruh anggota
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['label', 'status', 'user_id', 'per_page']);

        // Anggota hanya melihat setoran miliknya sendiri
        if ($request->user()->role === 'anggota' && $request->user()->member) {
            $filters['user_id'] = $request->user()->member->user_id;
        }

        $paginator = $this->savingsService->getSavings($filters);

        return response()->json([
            'success' => true,
            'message' => 'Rekap simpanan berhasil dimuat.',
            'data' => SavingsResource::collection($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/savings/pay — pencatatan pembayaran simpanan
     */
    public function pay(PaySavingsRequest $request): JsonResponse
    {
        $setoran = $this->savingsService->recordSavingsPayment($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran simpanan berhasil dicatat.',
            'data' => new SavingsResource($setoran->load('user:id,name,username')),
        ], 201);
    }

    /**
     * GET /api/v1/savings/billing-status — status tagihan bulan berjalan
     */
    public function billingStatus(Request $request): JsonResponse
    {
        // Anggota hanya boleh cek tagihan sendiri
        $memberId = $request->user()->role === 'anggota'
            ? $request->user()->member?->id
            : $request->integer('member_id');

        abort_if($memberId === null, 422, 'Parameter member_id wajib diisi.');

        return response()->json([
            'success' => true,
            'message' => 'Status tagihan berhasil dimuat.',
            'data' => $this->savingsService->getBillingStatus($memberId),
        ]);
    }

    /**
     * GET /api/v1/savings/wajib-overview — status setoran wajib seluruh anggota (bulan berjalan).
     */
    public function wajibOverview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Status setoran wajib anggota berhasil dimuat.',
            'data' => $this->savingsService->getWajibOverview(),
        ]);
    }

    /**
     * POST /api/v1/savings/generate-monthly-billing — tagihan massal bulanan
     */
    public function generateMonthlyBilling(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Tagihan bulanan berhasil dibuat untuk anggota aktif.',
            'data' => $this->savingsService->generateMonthlyBilling(),
        ]);
    }
}
