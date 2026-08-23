<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\BulkOperationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkOperationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BulkOperationService
    {
        return app(BulkOperationService::class);
    }

    public function test_bulk_invoice_generation_creates_one_invoice_per_customer(): void
    {
        $user = User::factory()->create();
        $customers = Customer::factory()->count(3)->create();

        $service = $this->service();
        $operation = $service->bulkGenerateInvoices(
            $customers->pluck('id')->all(),
            [
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'total_amount' => 50.00,
                'status' => 'pending',
            ],
            $user->id,
        );
        $service->processBulkInvoiceGeneration($operation);

        $this->assertEquals(3, Invoice::count());
        $operation->refresh();
        $this->assertEquals('completed', $operation->status);
        $this->assertEquals(3, $operation->processed_items);
        $this->assertEquals(0, $operation->failed_items);
        // each invoice belongs to one of the requested customers
        $this->assertEqualsCanonicalizing(
            $customers->pluck('id')->all(),
            Invoice::pluck('customer_id')->all(),
        );
    }

    public function test_export_clients_writes_csv_and_tracks_progress(): void
    {
        $user = User::factory()->create();
        Client::create(['name' => 'Alpha', 'email' => 'alpha@example.com']);
        Client::create(['name' => 'Beta', 'email' => 'beta@example.com']);

        $operation = $this->service()->exportClients([], $user->id);

        $operation->refresh();
        $this->assertEquals('completed', $operation->status);
        $this->assertEquals(2, $operation->processed_items);
        $this->assertNotNull($operation->result_file);

        $path = storage_path('app/'.$operation->result_file);
        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertStringContainsString('alpha@example.com', $contents);
        $this->assertStringContainsString('beta@example.com', $contents);

        @unlink($path);
    }

    public function test_export_neutralizes_csv_formula_injection(): void
    {
        $user = User::factory()->create();
        Client::create(['name' => '=cmd|/c calc!A1', 'email' => 'evil@example.com']);

        $operation = $this->service()->exportClients([], $user->id);
        $operation->refresh();

        $path = storage_path('app/'.$operation->result_file);
        $contents = file_get_contents($path);

        // value is prefixed with an apostrophe and the raw formula is no longer at cell start
        $this->assertStringContainsString("'=cmd", $contents);
        $this->assertStringNotContainsString(',=cmd', $contents);

        @unlink($path);
    }
}
