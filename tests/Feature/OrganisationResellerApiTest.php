<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganisationResellerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_creates_white_label_reseller_brand_and_domain(): void
    {
        [$user, $provider] = $this->operator();

        $child = $this->postJson('/api/organisations', [
            'owner_user_id' => $user->id,
            'name' => 'North Reseller',
            'organisation_type' => 'white_label',
            'slug' => 'north-reseller',
            'database_mode' => 'isolated',
        ])->assertCreated()
            ->assertJsonPath('parent_team_id', $provider->id)
            ->json();

        $brand = $this->postJson("/api/organisations/{$child['id']}/brands", [
            'name' => 'North Cloud',
            'slug' => 'north-cloud',
            'is_primary' => true,
            'theme' => ['primary_color' => '#123456'],
            'email_branding' => ['from_name' => 'North Support'],
        ])->assertCreated()->json();

        $domain = $this->postJson("/api/organisation-brands/{$brand['id']}/domains", [
            'domain' => 'portal.north.example',
            'is_primary' => true,
        ])->assertCreated()->json();
        $this->postJson("/api/brand-domains/{$domain['id']}/verify")
            ->assertOk()->assertJsonPath('is_verified', true);

        $this->getJson('http://portal.north.example/api/branding')
            ->assertOk()
            ->assertJsonPath('name', 'North Cloud')
            ->assertJsonPath('theme.primary_color', '#123456');
    }

    public function test_provider_creates_agreement_and_delegates_service(): void
    {
        [$user, $provider] = $this->operator();
        $reseller = Team::query()->create([
            'user_id' => $user->id,
            'parent_team_id' => $provider->id,
            'name' => 'Reseller',
            'organisation_type' => 'reseller',
            'personal_team' => false,
        ]);
        $subscription = Subscription::factory()->create([
            'team_id' => $provider->id,
            'price' => 100,
            'currency' => 'USD',
        ]);

        $agreement = $this->postJson('/api/resellers', [
            'reseller_team_id' => $reseller->id,
            'default_discount_percent' => 25,
            'revenue_share_percent' => 10,
            'credit_limit' => 1000,
        ])->assertCreated()->json();

        $this->postJson("/api/resellers/{$agreement['id']}/delegate", [
            'subscription_id' => $subscription->id,
            'retail_price' => 120,
        ])->assertCreated()
            ->assertJsonPath('wholesale_price', '90.00')
            ->assertJsonPath('retail_price', '120.00');
    }

    public function test_provider_cannot_manage_unrelated_organisation(): void
    {
        $this->operator();
        $unrelated = Team::factory()->create();

        $this->patchJson("/api/organisations/{$unrelated->id}", ['name' => 'Stolen'])->assertNotFound();
    }

    /** @return array{User, Team} */
    private function operator(): array
    {
        $user = User::factory()->create();
        $provider = Team::factory()->create(['user_id' => $user->id]);
        $user->update(['current_team_id' => $provider->id]);
        Sanctum::actingAs($user, [
            'organisations:read', 'organisations:write', 'resellers:read', 'resellers:write',
        ]);

        return [$user, $provider];
    }
}
