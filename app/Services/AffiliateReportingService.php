<?php

namespace App\Services;

use App\Models\Affiliate;
use Carbon\Carbon;

class AffiliateReportingService
{
    public function generateReport(Affiliate $affiliate, $startDate, $endDate): array
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $transactions = $affiliate->transactions()
            ->whereBetween(
                'created_at',
                [
                    $startDate,
                    $endDate,
                ]
            )
            ->get();

        $totalEarnings = $transactions->where(
            'type',
            'commission'
        )->sum('amount');
        $totalReferrals = $affiliate->referrals()->whereBetween(
            'created_at',
            [
                $startDate,
                $endDate,
            ]
        )->count();

        return [
            'affiliate' => $affiliate,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_earnings' => $totalEarnings,
            'total_referrals' => $totalReferrals,
            'transactions' => $transactions,
        ];
    }
}
