<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    /**
     * GET /api/v1/reports/dashboard-stats
     */
    public function dashboardStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Statistik dashboard berhasil dimuat.',
            'data' => $this->reportService->getDashboardStats(),
        ]);
    }

    /**
     * GET /api/v1/reports/financial?period_start=&period_end=
     */
    public function financial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan keuangan berhasil dimuat.',
            'data' => $this->reportService->getFinancialReport($validated['period_start'], $validated['period_end']),
        ]);
    }

    /**
     * GET /api/v1/reports/trash-volume
     */
    public function trashVolume(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rekapitulasi tonase sampah berhasil dimuat.',
            'data' => $this->reportService->getTrashVolumeReport($validated),
        ]);
    }

    /**
     * GET /api/v1/reports/export?report_id=1 — stream file laporan finalized.
     * ponytail: CSV untuk semua type — PDF/XLSX berstyle butuh library, tambah saat diminta.
     */
    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'report_id' => ['required', 'integer', 'exists:reports,id'],
        ]);

        $export = $this->reportService->exportReport($validated['report_id']);

        return response()->stream($export['content'], 200, $export['headers']);
    }
}
