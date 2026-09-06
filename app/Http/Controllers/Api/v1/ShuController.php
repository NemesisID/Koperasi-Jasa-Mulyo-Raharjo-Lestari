<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shu\PublishShuRequest;
use App\Http\Requests\Shu\SimulateShuRequest;
use App\Http\Resources\Shu\ShuPeriodResource;
use App\Services\ShuCalculationEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShuController extends Controller
{
    public function __construct(
        private readonly ShuCalculationEngine $shuEngine,
    ) {}

    /**
     * GET /api/v1/shu/periods — riwayat periode SHU
     */
    public function periods(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Riwayat periode SHU berhasil dimuat.',
            'data' => ShuPeriodResource::collection($this->shuEngine->getPeriods()),
        ]);
    }

    /**
     * POST /api/v1/shu/simulate — preview draft dividen per anggota.
     * Query ?save=1 menyimpan draft (shu_distributions + shu_members) untuk dipublish.
     */
    public function simulate(SimulateShuRequest $request): JsonResponse
    {
        $simulation = $this->shuEngine->simulateDistribution(
            $request->integer('year'),
            (float) $request->input('net_profit'),
            (float) ($request->input('shu_pool_percentage') ?? 20),
        );

        if ($request->boolean('save')) {
            $distribution = $this->shuEngine->saveDraft($simulation, $request->user());
            $simulation['shu_distribution_id'] = $distribution->id;
            $simulation['status'] = 'draft';
        }

        return response()->json([
            'success' => true,
            'message' => 'Simulasi SHU berhasil dihitung.',
            'data' => $simulation,
        ]);
    }

    /**
     * POST /api/v1/shu/publish — finalisasi & posting SHU massal
     */
    public function publish(PublishShuRequest $request): JsonResponse
    {
        $distribution = $this->shuEngine->publishDistribution(
            $request->integer('shu_distribution_id'),
            $request->user(),
        );

        return response()->json([
            'success' => true,
            'message' => "SHU tahun {$distribution->year} berhasil dibagikan ke {$distribution->recipient_count} anggota.",
            'data' => new ShuPeriodResource($distribution->load('handledBy:id,name')->loadCount('shuMembers')),
        ]);
    }

    /**
     * GET /api/v1/shu/my-history — riwayat dividen anggota (own)
     */
    public function myHistory(Request $request): JsonResponse
    {
        $member = $request->user()->member;
        abort_if($member === null, 403, 'Hanya anggota yang memiliki riwayat SHU.');

        return response()->json([
            'success' => true,
            'message' => 'Riwayat SHU berhasil dimuat.',
            'data' => $this->shuEngine->getMemberHistory($member->id),
        ]);
    }
}
