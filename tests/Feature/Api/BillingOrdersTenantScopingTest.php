<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Orders\Models\Cart;
use Liberu\Billing\Orders\Models\Order;
use Tests\TestCase;

class BillingOrdersTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_checkout_cannot_access_another_team_cart(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $cart = Cart::query()->create([
            'team_id' => $otherTeam->id,
            'currency' => 'USD',
            'items' => [],
            'status' => 'open',
        ]);
        $user->update(['current_team_id' => $user->currentTeam->id]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/orders/carts/'.$cart->getKey().'/checkout', [
            'subtotal_minor' => 100,
        ])->assertNotFound();

        $this->assertDatabaseHas('billing_order_carts', [
            'id' => $cart->getKey(), 'team_id' => $otherTeam->id, 'status' => 'open',
        ]);
    }

    public function test_order_mutations_cannot_access_another_team_order(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $order = Order::query()->create([
            'team_id' => $otherTeam->id,
            'order_number' => 'ORD-FOREIGN',
            'currency' => 'USD',
            'subtotal_minor' => 100,
            'discount_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => 100,
            'status' => 'approved',
            'fraud_status' => 'not_required',
        ]);
        $user->update(['current_team_id' => $user->currentTeam->id]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/orders/'.$order->getKey().'/fraud-review', [
            'fraud_status' => 'blocked',
        ])->assertNotFound();
        $this->postJson('/api/v1/billing/orders/'.$order->getKey().'/change-orders', [
            'reason' => 'Unauthorized change',
        ])->assertNotFound();
    }
}
