<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CreateTransactionRequest;
use App\Http\Resources\Finance\TransactionResource;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    /**
     * GET /api/v1/transactions — jurnal umum kas
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->transactionService->getTransactions(
            $request->only(['type', 'member_id', 'start_date', 'end_date', 'per_page']),
        );

        return response()->json([
            'success' => true,
            'message' => 'Jurnal kas berhasil dimuat.',
            'data' => TransactionResource::collection($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/transactions — transaksi kas umum
     */
    public function store(CreateTransactionRequest $request): JsonResponse
    {
        $transaction = $this->transactionService->createTransaction($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Transaksi kas berhasil dicatat.',
            'data' => new TransactionResource($transaction->load('member:id,member_code,name', 'category:id,name,type,group_type', 'officer:id,name')),
        ], 201);
    }
}
