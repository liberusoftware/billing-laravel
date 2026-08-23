<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ClientBillingReportService
{
    public function generateBillingHistory(Customer $customer, $startDate = null, $endDate = null)
    {
        $query = Invoice::where(
            'customer_id',
            $customer->id
        )
            ->with(
                [
                    'items',
                    'payments',
                ]
            )
            ->orderBy(
                'created_at',
                'desc'
            );

        if ($startDate) {
            $query->where(
                'created_at',
                '>=',
                $startDate
            );
        }

        if ($endDate) {
            $query->where(
                'created_at',
                '<=',
                $endDate
            );
        }

        return $query->get()->map(
            fn ($invoice): array => [
                'invoice_number' => $invoice->invoice_number,
                'date' => $invoice->created_at->format('Y-m-d'),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'amount' => $invoice->total_amount,
                'status' => $invoice->status,
                'paid_amount' => $invoice->payments->sum('amount'),
                'balance' => $invoice->total_amount - $invoice->payments->sum('amount'),
                'currency' => $invoice->currency,
            ]
        );
    }

    public function getPaymentStatus(Customer $customer): array
    {
        return [
            'total_invoiced' => Invoice::where(
                'customer_id',
                $customer->id
            )->sum('total_amount'),
            'total_paid' => Payment::whereHas(
                'invoice',
                function ($q) use ($customer): void {
                    $q->where(
                        'customer_id',
                        $customer->id
                    );
                }
            )->sum('amount'),
            'total_outstanding' => Invoice::where(
                'customer_id',
                $customer->id
            )
                ->where(
                    'status',
                    'pending'
                )
                ->sum('total_amount'),
            'overdue_amount' => Invoice::where(
                'customer_id',
                $customer->id
            )
                ->where(
                    'status',
                    'pending'
                )
                ->where(
                    'due_date',
                    '<',
                    now()
                )
                ->sum('total_amount'),
        ];
    }

    public function getPaymentTrends(Customer $customer)
    {
        return Payment::whereHas(
            'invoice',
            function ($q) use ($customer): void {
                $q->where(
                    'customer_id',
                    $customer->id
                );
            }
        )
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total_paid')
            )
            ->groupBy('month')
            ->orderBy(
                'month',
                'desc'
            )
            ->limit(12)
            ->get();
    }
}
