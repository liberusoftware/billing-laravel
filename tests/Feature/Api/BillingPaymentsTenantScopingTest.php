<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Payments\Models\Payment;
use Tests\TestCase;

class BillingPaymentsTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_mutations_cannot_access_another_team_payment(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $payment = Payment::query()->create([
            'team_id' => $otherTeam->id,
            'amount_minor' => 100,
            'currency' => 'USD',
            'status' => 'pending',
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/payments/'.$payment->getKey().'/reconcile', [
            'provider_reference' => 'external-1',
        ])->assertNotFound();

        $this->assertDatabaseMissing('billing_payment_reconciliations', [
            'payment_id' => $payment->getKey(),
        ]);
    }
}
