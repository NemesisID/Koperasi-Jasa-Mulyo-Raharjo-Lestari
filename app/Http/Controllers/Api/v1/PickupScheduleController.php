<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\PickupSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Penjemputan rutin per-warga (alur.md): anggota atur hari + slot waktu,
 * command harian pickups:generate-scheduled membuat tiketnya.
 */
class PickupScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $member = $request->user()->member;
        abort_if($member === null, 403, 'Hanya anggota yang memiliki penjemputan rutin.');

        return response()->json([
            'success' => true,
            'message' => 'Daftar rutinan penjemputan berhasil dimuat.',
            'data' => PickupSchedule::where('member_id', $member->id)->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $member = $request->user()->member;
        abort_if($member === null, 403, 'Hanya anggota yang memiliki penjemputan rutin.');

        $data = $request->validate([
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer', 'between:0,6'],
            'slots' => ['required', 'array', 'min:1'],
            'slots.*' => [Rule::in(['pagi', 'siang', 'sore'])],
        ]);

        $schedule = PickupSchedule::create([
            'member_id' => $member->id,
            'days' => array_values(array_unique($data['days'])),
            'slots' => array_values(array_unique($data['slots'])),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rutinan penjemputan berhasil disimpan.',
            'data' => $schedule,
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $member = $request->user()->member;
        abort_if($member === null, 403, 'Hanya anggota yang memiliki penjemputan rutin.');

        $schedule = PickupSchedule::where('member_id', $member->id)->findOrFail($id);
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rutinan penjemputan berhasil dihapus.',
        ]);
    }
}
