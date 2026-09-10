<?php

namespace App\Http\Controllers\Api\v1;

use App\Exports\RowsExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
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
     * GET /api/v1/reports/export — unduh laporan (alur.md: Excel & PDF).
     * On-the-flight: ?type=laba_rugi|trash_volume&period_start=&period_end=&format=csv|xlsx|pdf
     * Legacy: ?report_id=N (laporan finalized di DB) → format tetap berlaku.
     */
    public function export(Request $request): StreamedResponse|BinaryFileResponse|Response
    {
        $validated = $request->validate([
            'report_id' => ['nullable', 'integer', 'exists:reports,id'],
            'type' => ['nullable', Rule::in(['laba_rugi', 'trash_volume'])],
            'format' => ['nullable', Rule::in(['csv', 'xlsx', 'pdf'])],
            'period_start' => ['nullable', 'required_without:report_id', 'date'],
            'period_end' => ['nullable', 'required_without:report_id', 'date', 'after_or_equal:period_start'],
        ]);

        if (isset($validated['report_id'])) {
            $report = \App\Models\Report::finalized()->findOrFail($validated['report_id']);
            $type = $report->type === 'laba_rugi' ? 'laba_rugi' : 'trash_volume';
            $start = $report->period_start->toDateString();
            $end = $report->period_end->toDateString();
        } else {
            $type = $validated['type'];
            $start = $validated['period_start'];
            $end = $validated['period_end'];
        }

        $format = $validated['format'] ?? 'csv';
        $export = $this->reportService->getExportRows($type, $start, $end);
        $filename = $export['filename'];

        return match ($format) {
            'xlsx' => Excel::download(new RowsExport($export['rows']), $filename.'.xlsx'),
            'pdf' => Pdf::loadView('reports.export', ['rows' => $export['rows'], 'title' => $filename])
                ->download($filename.'.pdf'),
            default => response()->stream(
                function () use ($export) {
                    $fh = fopen('php://output', 'w');
                    foreach ($export['rows'] as $row) {
                        fputcsv($fh, $row);
                    }
                    fclose($fh);
                },
                200,
                [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
                ],
            ),
        };
    }
}
