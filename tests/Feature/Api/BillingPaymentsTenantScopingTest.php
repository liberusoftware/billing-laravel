<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Models\PaymentMethod;
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

    public function test_mandate_cannot_attach_another_team_payment_method(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->update(['current_team_id' => $user->currentTeam->id]);
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $method = PaymentMethod::query()->create([
            'team_id' => $otherTeam->id,
            'type' => 'card',
            'provider' => 'gateway',
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/payments/mandates', [
            'payment_method_id' => $method->getKey(),
            'provider' => 'gateway',
        ])->assertNotFound();

        $this->assertDatabaseMissing('billing_payment_mandates', [
            'payment_method_id' => $method->getKey(),
        ]);
    }

    public function test_payment_show_is_team_scoped_and_list_accepts_page_size(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->update(['current_team_id' => $user->currentTeam->id]);
        $payment = Payment::query()->create([
            'team_id' => $user->currentTeam->id,
            'amount_minor' => 1250,
            'currency' => 'USD',
            'status' => 'pending',
        ]);
        Sanctum::actingAs($user, ['billing.payments.read']);

        $this->getJson('/api/v1/billing/payments/'.$payment->getKey())
            ->assertOk()
            ->assertJsonPath('data.id', (string) $payment->getKey())
            ->assertJsonPath('data.attributes.amount_minor', 1250);

        $this->getJson('/api/v1/billing/payments?page[size]=1')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonCount(1, 'data');
    }
}
