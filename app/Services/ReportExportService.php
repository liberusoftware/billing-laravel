<?php

namespace App\Services;

use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ReportExportService
{
    public function __construct(protected ReportService $reportService) {}

    public function exportToCsv(Report $report): string
    {
        $data = $this->generateReportData($report);
        $filename = sprintf(
            'report_%s_%s.csv',
            $report->type,
            now()->format('Y-m-d')
        );

        $handle = fopen(
            'php://temp',
            'r+'
        );

        // Add headers
        fputcsv(
            $handle,
            array_keys(reset($data)),
            escape: '\\'
        );

        // Add data
        foreach ($data as $row) {
            fputcsv(
                $handle,
                $row,
                escape: '\\'
            );
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Storage::put(
            "reports/{$filename}",
            $csv
        );

        return $filename;
    }

    public function exportToPdf(Report $report): string
    {
        $data = $this->generateReportData($report);
        $filename = sprintf(
            'report_%s_%s.pdf',
            $report->type,
            now()->format('Y-m-d')
        );

        $pdf = Pdf::loadView(
            'reports.pdf',
            [
                'report' => $report,
                'data' => $data,
            ]
        );

        Storage::put(
            "reports/{$filename}",
            $pdf->output()
        );

        return $filename;
    }

    private function generateReportData(Report $report)
    {
        return match ($report->type) {
            'revenue' => $this->reportService->generateRevenueReport(
                $report->start_date,
                $report->end_date,
                $report->filters
            ),
            'outstanding' => $this->reportService->generateOutstandingBalanceReport(
                $report->filters
            ),
            'service' => $this->reportService->generateServiceReport(
                $report->start_date,
                $report->end_date,
                $report->filters
            ),
            default => throw new InvalidArgumentException('Invalid report type')
        };
    }
}
