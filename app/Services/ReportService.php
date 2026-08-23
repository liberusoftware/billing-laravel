<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Products_Service;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function generateRevenueReport($startDate, $endDate, array $filters = [])
    {
        $query = Payment::whereBetween(
            'created_at',
            [
                $startDate,
                $endDate,
            ]
        );

        if (! empty($filters['customer_id'])) {
            $query->whereHas(
                'invoice',
                function ($q) use ($filters): void {
                    $q->where(
                        'customer_id',
                        $filters['customer_id']
                    );
                }
            );
        }

        if (! empty($filters['service_id'])) {
            $query->whereHas(
                'invoice.items',
                function ($q) use ($filters): void {
                    $q->where(
                        'product_service_id',
                        $filters['service_id']
                    );
                }
            );
        }

        return $query->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(amount) as total'),
            'currency'
        )
            ->groupBy(
                'date',
                'currency'
            )
            ->get();
    }

    public function generateOutstandingBalanceReport(array $filters = [])
    {
        $query = Invoice::where(
            'status',
            'pending'
        )
            ->where(
                'due_date',
                '<',
                now()
            );

        if (! empty($filters['customer_id'])) {
            $query->where(
                'customer_id',
                $filters['customer_id']
            );
        }

        return $query->select(
            'customer_id',
            DB::raw('SUM(total_amount) as total_outstanding'),
            'currency'
        )
            ->with('customer:id,name,email')
            ->groupBy(
                'customer_id',
                'currency'
            )
            ->get();
    }

    public function generateServiceReport($startDate, $endDate, array $filters = [])
    {
        return Products_Service::select(
            'id',
            'name'
        )
            ->withCount(
                [
                    'invoiceItems' => function ($query) use ($startDate, $endDate): void {
                        $query->whereBetween(
                            'created_at',
                            [
                                $startDate,
                                $endDate,
                            ]
                        );
                    },
                ]
            )
            ->withSum(
                [
                    'invoiceItems' => function ($query) use ($startDate, $endDate): void {
                        $query->whereBetween(
                            'created_at',
                            [
                                $startDate,
                                $endDate,
                            ]
                        );
                    },
                ],
                'total_price'
            )
            ->get();
    }
}
