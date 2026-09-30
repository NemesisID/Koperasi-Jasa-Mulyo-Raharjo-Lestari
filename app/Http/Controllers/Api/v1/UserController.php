<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * GET /api/v1/users — ?trashed=1 untuk daftar akun yang sudah diarsipkan.
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->userService->getUsersPaginated($request->only(['role', 'search', 'per_page', 'trashed']));

        return (new UserCollection($paginator))->response();
    }

    /**
     * POST /api/v1/users
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Akun pengguna berhasil dibuat.',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * GET /api/v1/users/{id}
     */
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail pengguna berhasil dimuat.',
            'data' => new UserResource($this->userService->getUser($id)),
        ]);
    }

    /**
     * PUT /api/v1/users/{id}
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->updateUser($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Akun pengguna berhasil diperbarui.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * DELETE /api/v1/users/{id} — arsipkan akun, bukan hapus permanen
     * (riwayat transaksi tetap merujuk user_id, akun bisa dipulihkan).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->userService->deleteUser($request->user(), $id);

        return response()->json([
            'success' => true,
            'message' => 'Akun pengguna berhasil diarsipkan. Riwayatnya tetap tersimpan dan dapat dipulihkan.',
            'data' => null,
        ]);
    }

    /**
     * PATCH /api/v1/users/{id}/restore
     */
    public function restore(int $id): JsonResponse
    {
        $user = $this->userService->restoreUser($id);

        return response()->json([
            'success' => true,
            'message' => 'Akun pengguna berhasil dipulihkan.',
            'data' => new UserResource($user->load('member')),
        ]);
    }
}
