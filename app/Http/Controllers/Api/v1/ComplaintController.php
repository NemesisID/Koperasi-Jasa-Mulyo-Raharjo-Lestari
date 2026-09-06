<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\ResolveComplaintRequest;
use App\Http\Requests\Complaint\SubmitComplaintRequest;
use App\Http\Resources\Complaint\ComplaintResource;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ComplaintController extends Controller
{
    public function __construct(
        private readonly ComplaintService $complaintService,
    ) {}

    /**
     * GET /api/v1/complaints
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'pickup_id', 'per_page']);

        // Anggota hanya melihat komplain miliknya sendiri
        if ($request->user()->role === 'anggota') {
            $filters['member_id'] = $request->user()->member?->id;
        }

        $paginator = $this->complaintService->getComplaints($filters);

        return response()->json([
            'success' => true,
            'message' => 'Daftar komplain berhasil dimuat.',
            'data' => ComplaintResource::collection($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/complaints
     */
    public function store(SubmitComplaintRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Simpan bukti foto (opsional) ke disk publik
        if ($request->hasFile('proof_image')) {
            $data['proof_image'] = $request->file('proof_image')->store('complaint-proofs', 'public');
        }

        $complaint = $this->complaintService->submitComplaint($request->user(), $data);

        return response()->json([
            'success' => true,
            'message' => 'Komplain berhasil diajukan dan akan diproses oleh pengurus.',
            'data' => new ComplaintResource($this->complaintService->getComplaint($complaint->id)),
        ], 201);
    }

    /**
     * GET /api/v1/complaints/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $complaint = $this->complaintService->getComplaint($id);

        if ($request->user()->role === 'anggota' && $complaint->member?->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException('Akses ditolak. Anda hanya dapat mengakses komplain Anda sendiri.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail komplain berhasil dimuat.',
            'data' => new ComplaintResource($complaint),
        ]);
    }

    /**
     * PATCH /api/v1/complaints/{id}/resolve
     */
    public function resolve(ResolveComplaintRequest $request, int $id): JsonResponse
    {
        $complaint = $this->complaintService->resolveComplaint($id, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Komplain berhasil diselesaikan.',
            'data' => new ComplaintResource($complaint),
        ]);
    }
}
