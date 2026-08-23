<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\ProjectBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProjectBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoicing_time_creates_invoice_and_stamps_entries(): void
    {
        $customer = Customer::factory()->create();
        $project = Project::factory()->create(['customer_id' => $customer->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $oneHour = TimeEntry::factory()->create([
            'task_id' => $task->id, 'duration_seconds' => 3600, 'rate' => 50,
            'is_billable' => true, 'invoiced_at' => null,
        ]);
        $halfHour = TimeEntry::factory()->create([
            'task_id' => $task->id, 'duration_seconds' => 1800, 'rate' => 50,
            'is_billable' => true, 'invoiced_at' => null,
        ]);

        $invoice = app(ProjectBillingService::class)
            ->invoiceTime($project, [$oneHour->id, $halfHour->id]);

        // 1h*50 + 0.5h*50 = 75
        $this->assertEqualsWithDelta(75.00, (float) $invoice->total_amount, 0.01);
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertNotNull($oneHour->fresh()->invoiced_at);
        $this->assertNotNull($halfHour->fresh()->invoiced_at);
    }

    public function test_already_invoiced_time_is_excluded(): void
    {
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);
        $done = TimeEntry::factory()->create([
            'task_id' => $task->id, 'is_billable' => true, 'invoiced_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);

        app(ProjectBillingService::class)->invoiceTime($project, [$done->id]);
    }
}
