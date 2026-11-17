<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportRequest;
use App\Services\ReportService;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(ReportRequest $request): InertiaResponse
    {
        return Inertia::render('reports/index', [
            'report' => $this->reports->generate($request->input('from'), $request->input('to')),
        ]);
    }

    public function export(ReportRequest $request): Response
    {
        $report = $this->reports->generate($request->input('from'), $request->input('to'));
        $filename = 'laporan-penjualan-'.$report['period']['from'].'-'.$report['period']['to'].'.csv';

        return response($this->reports->toCsv($report), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ]);
    }
}
