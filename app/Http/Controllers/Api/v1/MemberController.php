<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Requests\Member\UpdateMemberStatusRequest;
use App\Http\Resources\Member\MemberResource;
use App\Http\Resources\User\UserResource;
use App\Services\AuthService;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MemberController extends Controller
{
    public function __construct(
        private readonly MemberService $memberService,
        private readonly AuthService $authService,
    ) {}

    /**
     * GET /api/v1/members
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->memberService->getMembersPaginated(
            $request->only(['status', 'category_id', 'search', 'per_page']),
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
     * POST /api/v1/members/register
     *
     * Registrasi anggota oleh pengurus: buat user (role anggota) + member
     * (member_code autofill, kategori rumah/pasar) dalam satu transaksi.
     */
    public function register(RegisterMemberRequest $request): JsonResponse
    {
        $result = $this->authService->registerMember($request->validated(), 'aktif');

        return response()->json([
            'success' => true,
            'message' => 'Registrasi anggota oleh pengurus berhasil.',
            'data' => [
                'user' => new UserResource($result['user']),
                'member' => new MemberResource($result['member']),
            ],
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
}
