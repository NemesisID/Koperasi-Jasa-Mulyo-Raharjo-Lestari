<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\ApproveWithdrawRequest;
use App\Http\Requests\Wallet\WithdrawCashRequest;
use App\Http\Requests\Wallet\WithdrawRequestForm;
use App\Http\Resources\Wallet\WalletMutationResource;
use App\Http\Resources\Wallet\WithdrawRequestResource;
use App\Models\WithdrawRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    /**
     * GET /api/v1/wallet/summary — ringkasan saldo anggota (own)
     */
    public function summary(Request $request): JsonResponse
    {
        $memberId = $this->resolveMemberId($request);

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan dompet berhasil dimuat.',
            'data' => $this->walletService->getMemberWalletSummary($memberId),
        ]);
    }

    /**
     * GET /api/v1/wallet/mutations — riwayat mutasi dompet
     */
    public function mutations(Request $request): JsonResponse
    {
        $memberId = $this->resolveMemberId($request);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat mutasi dompet berhasil dimuat.',
            'data' => WalletMutationResource::collection($this->walletService->getMemberMutations($memberId)),
        ]);
    }

    /**
     * POST /api/v1/wallet/withdraw — pengajuan tarik saldo (anggota)
     */
    public function withdraw(WithdrawRequestForm $request): JsonResponse
    {
        $withdraw = $this->walletService->requestWithdraw($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan penarikan saldo berhasil dibuat, menunggu verifikasi pengurus.',
            'data' => new WithdrawRequestResource($withdraw->load('member:id,member_code,name')),
        ], 201);
    }

    /**
     * POST /api/v1/wallet/withdraw-cash — penarikan tunai oleh petugas/pengurus
     * (alur.md): search member, isi nominal + bukti foto, saldo langsung terpotong.
     */
    public function withdrawCash(WithdrawCashRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('withdrawals', 'public');
        }

        $withdraw = $this->walletService->withdrawCash($data, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Penarikan tunai berhasil. Saldo anggota telah dipotong dan kas tercatat keluar.',
            'data' => new WithdrawRequestResource($withdraw->load('member:id,member_code,name', 'processor:id,name')),
        ], 201);
    }

    /**
     * GET /api/v1/wallet/withdraw-requests — antrean penarikan (pengurus)
     */
    public function withdrawRequests(Request $request): JsonResponse
    {
        $paginator = WithdrawRequest::query()
            ->with('member:id,member_code,name')
            ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar permintaan penarikan berhasil dimuat.',
            'data' => WithdrawRequestResource::collection($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    /**
     * PATCH /api/v1/wallet/withdraw-requests/{id}/approve — eksekusi penarikan
     */
    public function approveWithdrawal(ApproveWithdrawRequest $request, int $id): JsonResponse
    {
        $withdraw = $this->walletService->processWithdrawApproval($id, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => $withdraw->status === 'disetujui'
                ? 'Penarikan disetujui. Saldo anggota telah dipotong dan kas tercatat keluar.'
                : 'Permintaan penarikan ditolak.',
            'data' => new WithdrawRequestResource($withdraw->load('member:id,member_code,name', 'processor:id,name')),
        ]);
    }

    /**
     * Anggota selalu dompet sendiri; pengurus boleh query member lain via ?member_id=.
     */
    private function resolveMemberId(Request $request): int
    {
        if ($request->user()->role === 'anggota') {
            abort_if($request->user()->member === null, 403, 'Hanya anggota yang memiliki dompet saldo.');
            $memberId = $request->user()->member->id;
        } else {
            $memberId = $request->integer('member_id');
        }

        abort_if($memberId === 0, 422, 'Parameter member_id wajib diisi.');

        return $memberId;
    }
}
