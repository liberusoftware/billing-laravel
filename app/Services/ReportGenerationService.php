<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportGenerationService
{
    public function __construct(protected BillingService $billingService) {}

    public function generateReport(Report $report): string
    {
        $data = $this->gatherReportData($report);

        return match ($report->format) {
            'pdf' => $this->generatePdfReport(
                $data,
                $report
            ),
            'csv' => $this->generateCsvReport(
                $data,
                $report
            ),
            'excel' => $this->generateExcelReport(
                $data,
                $report
            ),
            default => throw new Exception('Unsupported report format')
        };
    }

    protected function gatherReportData(Report $report)
    {
        $clientBillingService = new ClientBillingReportService;

        return match ($report->type) {
            'billing_summary' => $this->billingService->getBillingSummary($report->parameters),
            'revenue_report' => $this->billingService->getRevenueReport($report->parameters),
            'customer_activity' => $this->billingService->getCustomerActivityReport($report->parameters),
            'client_billing' => [
                'customer' => Customer::find($report->parameters['customer_id']),
                'billing_history' => $clientBillingService->generateBillingHistory(
                    Customer::find($report->parameters['customer_id']),
                    $report->parameters['start_date'] ?? null,
                    $report->parameters['end_date'] ?? null
                ),
                'payment_status' => $clientBillingService->getPaymentStatus(
                    Customer::find($report->parameters['customer_id'])
                ),
                'payment_trends' => $clientBillingService->getPaymentTrends(
                    Customer::find($report->parameters['customer_id'])
                ),
                'max_monthly_payment' => $clientBillingService->getPaymentTrends(
                    Customer::find($report->parameters['customer_id'])
                )->max('total_paid'),
            ],
            default => throw new Exception('Unsupported report type')
        };
    }

    protected function generatePdfReport($data, Report $report): string
    {
        $pdf = Pdf::loadView(
            "reports.templates.{$report->type}",
            ['data' => $data]
        );
        $filename = "report_{$report->id}.pdf";
        Storage::put(
            "reports/{$filename}",
            $pdf->output()
        );

        return $filename;
    }

    protected function generateCsvReport($data, Report $report): string
    {
        $filename = "report_{$report->id}.csv";
        $handle = fopen(
            Storage::path("reports/{$filename}"),
            'w'
        );

        fputcsv(
            $handle,
            array_keys(reset($data)),
            escape: '\\'
        );
        foreach ($data as $row) {
            fputcsv(
                $handle,
                $row,
                escape: '\\'
            );
        }

        fclose($handle);

        return $filename;
    }

    protected function generateExcelReport($data, Report $report): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = array_keys(reset($data));
        foreach ($headers as $col => $header) {
            $sheet->setCellValue([$col + 1, 1], $header);
        }

        foreach ($data as $row => $rowData) {
            foreach ($rowData as $col => $value) {
                $sheet->setCellValue([$col + 1, $row + 2], $value);
            }
        }

        $filename = "report_{$report->id}.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::path("reports/{$filename}"));

        return $filename;
    }
}
