<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pickup\CancelPickupRequest;
use App\Http\Requests\Pickup\CreatePickupTicketRequest;
use App\Http\Requests\Pickup\SubmitWeighItemsRequest;
use App\Http\Resources\Pickup\PickupResource;
use App\Http\Resources\Pickup\ReceiptResource;
use App\Services\TrashWeighingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PickupController extends Controller
{
    public function __construct(
        private readonly TrashWeighingService $weighingService,
    ) {}

    /**
     * GET /api/v1/pickups — riwayat/daftar penjemputan.
     * Anggota hanya melihat pickup miliknya sendiri.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'date', 'member_id', 'search', 'per_page']);

        if ($request->user()->role === 'anggota') {
            abort_if($request->user()->member === null, 403, 'Hanya anggota yang memiliki riwayat penjemputan.');
            $filters['member_id'] = $request->user()->member->id;
        }

        $paginator = $this->weighingService->getPickups($filters);

        return response()->json([
            'success' => true,
            'message' => 'Daftar penjemputan berhasil dimuat.',
            'data' => PickupResource::collection($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/pickups
     */
    public function store(CreatePickupTicketRequest $request): JsonResponse
    {
        $pickup = $this->weighingService->createTicket($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Tiket penjemputan berhasil dibuat.',
            'data' => new PickupResource($pickup->load('member:id,member_code,name')),
        ], 201);
    }

    /**
     * POST /api/v1/pickups/generate-routine — trigger manual pickup rutin (auto trigger).
     */
    public function generateRoutine(): JsonResponse
    {
        $exit = Artisan::call('pickups:generate-routine');

        return response()->json([
            'success' => $exit === 0,
            'message' => Artisan::output(),
        ]);
    }

    /**
     * POST /api/v1/pickups/{id}/photo — upload foto dokumentasi pengambilan (petugas).
     */
    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $pickup = $this->weighingService->getPickup($id);
        $pickup->update([
            'photo_path' => $request->file('photo')->store('pickups', 'public'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto dokumentasi berhasil diunggah.',
            'data' => ['photo_url' => $pickup->photoUrl()],
        ]);
    }

    /**
     * POST /api/v1/pickups/{id}/weigh-items
     */
    public function weighItems(SubmitWeighItemsRequest $request, int $id): JsonResponse
    {
        $pickup = $this->weighingService->weighAndComplete($id, $request->validated('items'), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Penimbangan selesai. Saldo bersih telah dikreditkan ke anggota.',
            'data' => [
                'pickup' => new PickupResource($pickup),
                'net_earned' => $pickup->total_net,
                'receipt_number' => $pickup->items->first()?->transaction?->transaction_code,
            ],
        ]);
    }

    /**
     * GET /api/v1/pickups/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $pickup = $this->weighingService->getPickup($id);
        $this->authorizeMemberAccess($request, $pickup);

        return response()->json([
            'success' => true,
            'message' => 'Detail penjemputan berhasil dimuat.',
            'data' => new PickupResource($pickup),
        ]);
    }

    /**
     * GET /api/v1/pickups/{id}/receipt
     */
    public function receipt(Request $request, int $id): JsonResponse
    {
        $pickup = $this->weighingService->getPickup($id);
        $this->authorizeMemberAccess($request, $pickup);

        return response()->json([
            'success' => true,
            'message' => 'Nota digital berhasil dimuat.',
            'data' => new ReceiptResource($pickup),
        ]);
    }

    /**
     * PATCH /api/v1/pickups/{id}/cancel
     */
    public function cancel(CancelPickupRequest $request, int $id): JsonResponse
    {
        $pickup = $this->weighingService->cancelPickup($id, $request->validated('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Transaksi timbang dibatalkan. Saldo dan jurnal kas telah di-rollback.',
            'data' => new PickupResource($pickup),
        ]);
    }

    /**
     * Anggota hanya boleh mengakses pickup miliknya sendiri.
     */
    private function authorizeMemberAccess(Request $request, $pickup): void
    {
        if ($request->user()->role === 'anggota' && $pickup->member?->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException('Akses ditolak. Anda hanya dapat mengakses transaksi Anda sendiri.');
        }
    }
}
