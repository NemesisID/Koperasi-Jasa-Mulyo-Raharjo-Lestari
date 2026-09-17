<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Requests\Member\UpdateMemberStatusRequest;
use App\Http\Resources\Member\MemberResource;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MemberController extends Controller
{
    public function __construct(
        private readonly MemberService $memberService,
    ) {}

    /**
     * GET /api/v1/members
     * Query ?trashed=1 untuk daftar anggota yang sudah diarsipkan.
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->memberService->getMembersPaginated(
            $request->only(['status', 'category_id', 'officer_id', 'search', 'per_page', 'trashed']),
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar anggota berhasil dimuat.',
            'data' => MemberResource::collection($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/members
     */
    public function store(StoreMemberRequest $request): JsonResponse
    {
        $member = $this->memberService->createMember($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Anggota berhasil ditambahkan.',
            'data' => new MemberResource($member->load('category')),
        ], 201);
    }

    /**
     * GET /api/v1/members/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $member = $this->memberService->getMember($id);

        // Anggota hanya boleh melihat profil sendiri
        if ($request->user()->role === 'anggota' && $member->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException('Akses ditolak. Anda hanya dapat melihat data anggota Anda sendiri.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail anggota berhasil dimuat.',
            'data' => new MemberResource($member),
        ]);
    }

    /**
     * PUT /api/v1/members/{id}
     */
    public function update(UpdateMemberRequest $request, int $id): JsonResponse
    {
        $member = $this->memberService->updateMember($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data anggota berhasil diperbarui.',
            'data' => new MemberResource($member),
        ]);
    }

    /**
     * PATCH /api/v1/members/{id}/status
     */
    public function updateStatus(UpdateMemberStatusRequest $request, int $id): JsonResponse
    {
        $member = $this->memberService->updateStatus($id, $request->validated('status'));

        return response()->json([
            'success' => true,
            'message' => 'Status anggota berhasil diperbarui.',
            'data' => new MemberResource($member),
        ]);
    }

    /**
     * DELETE /api/v1/members/{id} — arsipkan anggota, bukan hapus permanen.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->memberService->deleteMember($id);

        return response()->json([
            'success' => true,
            'message' => 'Anggota berhasil dihapus. Data riwayatnya tetap tersimpan dan dapat dipulihkan.',
            'data' => null,
        ]);
    }

    /**
     * PATCH /api/v1/members/{id}/restore
     */
    public function restore(int $id): JsonResponse
    {
        $member = $this->memberService->restoreMember($id);

        return response()->json([
            'success' => true,
            'message' => 'Anggota berhasil dipulihkan.',
            'data' => new MemberResource($member->load('category', 'officer:id,name')),
        ]);
    }
}
