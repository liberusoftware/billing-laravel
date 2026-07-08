<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectInvoiceLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_lists_its_invoices_with_payment_status(): void
    {
        $project = Project::factory()->create();

        $invoice = Invoice::factory()->create([
            'customer_id' => $project->customer_id,
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $this->assertTrue($project->invoices->contains($invoice));
        $this->assertSame('pending', $project->invoices->first()->status);
        $this->assertTrue($invoice->project->is($project));
    }
}
