<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\AuditLogService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_report_sums_payments_by_date_and_currency(): void
    {
        Payment::factory()->create(['amount' => 100.00, 'currency' => 'USD', 'created_at' => '2026-06-10 09:00:00']);
        Payment::factory()->create(['amount' => 50.00, 'currency' => 'USD', 'created_at' => '2026-06-10 15:00:00']);
        Payment::factory()->create(['amount' => 200.00, 'currency' => 'EUR', 'created_at' => '2026-06-10 10:00:00']);
        // out of range — excluded
        Payment::factory()->create(['amount' => 999.00, 'currency' => 'USD', 'created_at' => '2026-01-01 10:00:00']);

        $report = app(ReportService::class)->generateRevenueReport('2026-06-01 00:00:00', '2026-06-30 23:59:59');

        $usd = $report->first(fn ($r): bool => $r->currency === 'USD' && $r->date === '2026-06-10');
        $eur = $report->first(fn ($r): bool => $r->currency === 'EUR' && $r->date === '2026-06-10');

        $this->assertEquals(150.00, (float) $usd->total); // 100 + 50, same day/currency
        $this->assertEquals(200.00, (float) $eur->total);
        $this->assertNull($report->first(fn ($r): bool => (float) $r->total === 999.00));
    }

    public function test_audit_log_records_event_with_old_and_new_values(): void
    {
        $customer = Customer::factory()->create();

        app(AuditLogService::class)->log('updated', $customer, ['name' => 'Old'], ['name' => 'New']);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'auditable_type' => Customer::class,
            'auditable_id' => $customer->id,
        ]);
        $log = AuditLog::first();
        $this->assertEquals(['name' => 'Old'], $log->old_values);
        $this->assertEquals(['name' => 'New'], $log->new_values);
    }
}
