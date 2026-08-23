<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\OrderFormTemplates\Pages\CreateOrderFormTemplate;
use App\Models\OrderFormTemplate;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderFormTemplateResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_order_form_template_with_offered_plans(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $planA = SubscriptionPlan::create([
            'name' => 'Starter',
            'code' => 'starter',
            'price' => 9.99,
            'currency' => 'USD',
            'is_active' => true,
        ]);
        $planB = SubscriptionPlan::create([
            'name' => 'Pro',
            'code' => 'pro',
            'price' => 19.99,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $panel->boot();
        Filament::setTenant($user->currentTeam);

        Livewire::test(CreateOrderFormTemplate::class)
            ->fillForm([
                'name' => 'Standard order form',
                'slug' => 'standard',
                'description' => 'Default order form.',
                'is_active' => true,
                'config.plan_ids' => [$planA->id, $planB->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('order_form_templates', [
            'name' => 'Standard order form',
            'slug' => 'standard',
            'is_active' => true,
        ]);

        $template = OrderFormTemplate::firstOrFail();
        $this->assertSame([$planA->id, $planB->id], $template->offeredPlanIds());
    }
}
