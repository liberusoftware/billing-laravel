<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Domains\Models\Domain;
use Tests\TestCase;

class BillingDomainsTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_reads_and_mutations_cannot_access_another_team_domain(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $domain = Domain::query()->create([
            'team_id' => $otherTeam->id,
            'name' => 'other.example.test',
            'status' => 'available',
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/billing/domains/'.$domain->getKey())->assertNotFound();
        $this->postJson('/api/v1/billing/domains/'.$domain->getKey().'/renew')->assertNotFound();

        $this->assertDatabaseHas('billing_domains_records', [
            'id' => $domain->getKey(), 'team_id' => $otherTeam->id, 'status' => 'available',
        ]);
    }
}
