<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\BusinessConnector;
use App\Enums\ConnectorType;
use App\Models\ClientContact;
use App\Models\Customer;
use App\Models\DomainEventMessage;
use App\Models\ExternalConnection;
use App\Models\Lead;
use App\Models\Team;
use App\Services\IntegrationSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_contacts_sync_idempotently_to_crm(): void
    {
        $team = Team::factory()->create();
        $customer = Customer::factory()->create(['team_id' => $team->id, 'lifecycle_status' => 'active']);
        ClientContact::query()->create([
            'customer_id' => $customer->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.test',
            'contact_type' => 'technical',
        ]);
        ExternalConnection::factory()->create([
            'team_id' => $customer->team_id,
            'connector_type' => ConnectorType::Crm,
        ]);
        $connector = new FakeBusinessConnector();
        $service = new IntegrationSyncService($connector);

        $this->assertSame(1, $service->synchronizeCustomer($customer));
        $this->assertSame(1, $service->synchronizeCustomer($customer));

        $this->assertCount(2, $connector->pushes);
        $this->assertDatabaseCount('external_sync_records', 2);
        $this->assertDatabaseHas('external_sync_records', ['resource_type' => 'customers']);
        $this->assertDatabaseHas('external_sync_records', ['resource_type' => 'contacts']);
    }

    public function test_lead_conversion_and_opportunity_billing_sync_to_external_systems(): void
    {
        $teamId = Team::factory()->create()->id;
        ExternalConnection::factory()->create(['team_id' => $teamId, 'connector_type' => ConnectorType::Crm]);
        ExternalConnection::factory()->create(['team_id' => $teamId, 'connector_type' => ConnectorType::Accounting]);
        $lead = Lead::query()->create([
            'team_id' => $teamId,
            'name' => 'Prospect Ltd',
            'email' => 'prospect@example.test',
            'status' => 'new',
        ]);
        $service = new IntegrationSyncService(new FakeBusinessConnector());

        $customer = $service->convertLead($lead);
        $invoice = $service->billOpportunity($customer, [
            'amount' => 1250,
            'currency' => 'USD',
            'name' => 'Cloud migration',
        ]);

        $this->assertSame('converted', $lead->refresh()->status);
        $this->assertSame('Prospect Ltd', $customer->name);
        $this->assertEquals(1250, (float) $invoice->total_amount);
        $this->assertDatabaseHas('external_sync_records', [
            'resource_type' => 'invoices',
            'local_id' => (string) $invoice->id,
        ]);
    }
}

final class FakeBusinessConnector implements BusinessConnector
{
    /** @var list<array{resource: string, local_id: string}> */
    public array $pushes = [];

    public function push(
        ExternalConnection $connection,
        string $resource,
        string $localId,
        array $payload,
        ?string $remoteId = null
    ): string {
        $this->pushes[] = ['resource' => $resource, 'local_id' => $localId];

        return $remoteId ?? "remote-{$resource}-{$localId}";
    }

    public function pull(ExternalConnection $connection, string $resource, ?string $cursor = null): array
    {
        return ['items' => [], 'cursor' => null];
    }

    public function publish(ExternalConnection $connection, DomainEventMessage $event): void {}
}
