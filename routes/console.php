<?php

use App\Models\Report;
use App\Services\BillingService;
use App\Services\ReportGenerationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    app(BillingService::class)->processRecurringBilling();
})->daily();

Schedule::command('invoices:send-reminders')->daily();
Schedule::command('invoices:process-reminders')->daily();
Schedule::command('audit:prune')->daily();
Schedule::command('webhooks:process')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('services:suspend-overdue --days=7')->daily();
Schedule::command('usage:import')->daily();
Schedule::command('tickets:escalate')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('domains:sync')->daily();

Schedule::call(function (): void {
    $reports = Report::query()
        ->whereNotNull('schedule')
        ->where(function ($query): void {
            $query->whereNull('last_generated_at')
                ->orWhere('last_generated_at', '<=', now()->subHour());
        })
        ->get();

    foreach ($reports as $report) {
        try {
            if (shouldGenerateReport($report)) {
                app(ReportGenerationService::class)->generateReport($report);
                $report->update([
                    'last_generated_at' => now(),
                    'last_generation_status' => 'success',
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to generate report: '.$e->getMessage(), [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);
            $report->update([
                'last_generation_status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);
        }
    }
})->hourly();

if (! function_exists('shouldGenerateReport')) {
    function shouldGenerateReport(Report $report): bool
    {
        if (! $report->last_generated_at) {
            return true;
        }

        $schedule = $report->schedule;

        if (! isset($schedule['frequency'])) {
            return false;
        }

        $lastGenerated = $report->last_generated_at;

        return match ($schedule['frequency']) {
            'daily' => $lastGenerated->diffInDays(now()) >= 1,
            'weekly' => $lastGenerated->diffInWeeks(now()) >= 1,
            'monthly' => $lastGenerated->diffInMonths(now()) >= 1,
            'hourly' => $lastGenerated->diffInHours(now()) >= 1,
            default => false,
        };
    }
}
