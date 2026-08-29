<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerBillingSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_billing_summary_is_scoped_and_exposes_history_status_and_trends(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        $customer = Customer::factory()->create(['team_id' => $team->getKey()]);
        $createdAt = now()->subMonth();
        $invoice = DB::table('billing_invoices')->insertGetId([
            'team_id' => $team->getKey(), 'customer_id' => $customer->getKey(), 'number' => 'INV-API-1', 'status' => 'finalized',
            'currency' => 'USD', 'subtotal_minor' => 1000, 'tax_minor' => 0, 'total_minor' => 1000,
            'due_at' => now()->subDay(), 'finalized_at' => $createdAt, 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
        $payment = DB::table('billing_payments')->insertGetId([
            'team_id' => $team->getKey(), 'customer_id' => $customer->getKey(), 'amount_minor' => 400, 'currency' => 'USD',
            'status' => 'captured', 'captured_at' => $createdAt, 'refunded_minor' => 0, 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
        DB::table('billing_payment_allocations')->insert([
            'payment_id' => $payment, 'invoice_id' => $invoice, 'amount_minor' => 400, 'currency' => 'USD', 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/billing/customer-portal/billing/'.$customer->getKey())
            ->assertOk()
            ->assertJsonPath('data.status.total_invoiced', 1000)
            ->assertJsonPath('data.status.total_paid', 400)
            ->assertJsonPath('data.history.0.balance_minor', 600)
            ->assertJsonPath('data.trends.0.total_paid', 400);
    }

    public function test_customer_billing_summary_rejects_a_foreign_customer(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $foreignTeam = Team::factory()->create();
        $customer = Customer::factory()->create(['team_id' => $foreignTeam->getKey()]);
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/billing/customer-portal/billing/'.$customer->getKey())
            ->assertForbidden();
    }
}
