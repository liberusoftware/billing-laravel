<?php

namespace Tests\Unit\Services;

use App\Models\PackageGroup;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PackageGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageGroupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PackageGroupService $packageGroupService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->packageGroupService = new PackageGroupService;
    }

    public function test_can_create_package_group(): void
    {
        $group = $this->packageGroupService->createGroup([
            'name' => 'Web Hosting',
            'description' => 'Web hosting packages',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(PackageGroup::class, $group);
        $this->assertEquals('Web Hosting', $group->name);
        $this->assertTrue($group->is_active);
    }

    public function test_can_update_package_group(): void
    {
        $group = PackageGroup::create([
            'name' => 'Old Name',
            'is_active' => true,
        ]);

        $updated = $this->packageGroupService->updateGroup($group, [
            'name' => 'New Name',
            'description' => 'Updated description',
        ]);

        $this->assertEquals('New Name', $updated->name);
        $this->assertEquals('Updated description', $updated->description);
    }

    public function test_can_delete_package_group(): void
    {
        $group = PackageGroup::create([
            'name' => 'To Delete',
            'is_active' => true,
        ]);

        $this->packageGroupService->deleteGroup($group);

        $this->assertDatabaseMissing('package_groups', ['id' => $group->id]);
    }

    public function test_can_add_package_to_group(): void
    {
        $group = PackageGroup::create([
            'name' => 'Web Hosting',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Starter Plan',
            'code' => 'starter',
            'price' => 9.99,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $this->packageGroupService->addPackage($group, $plan, 0);

        $this->assertDatabaseHas('package_group_items', [
            'package_group_id' => $group->id,
            'subscription_plan_id' => $plan->id,
        ]);
    }

    public function test_can_remove_package_from_group(): void
    {
        $group = PackageGroup::create([
            'name' => 'Web Hosting',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Starter Plan',
            'code' => 'starter',
            'price' => 9.99,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $group->packages()->attach($plan->id, ['sort_order' => 0]);

        $this->packageGroupService->removePackage($group, $plan);

        $this->assertDatabaseMissing('package_group_items', [
            'package_group_id' => $group->id,
            'subscription_plan_id' => $plan->id,
        ]);
    }

    public function test_can_reorder_packages(): void
    {
        $group = PackageGroup::create([
            'name' => 'Web Hosting',
            'is_active' => true,
        ]);

        $plan1 = SubscriptionPlan::create([
            'name' => 'Plan A',
            'code' => 'plan-a',
            'price' => 9.99,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $plan2 = SubscriptionPlan::create([
            'name' => 'Plan B',
            'code' => 'plan-b',
            'price' => 19.99,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $group->packages()->attach($plan1->id, ['sort_order' => 0]);
        $group->packages()->attach($plan2->id, ['sort_order' => 1]);

        // Reverse the order
        $this->packageGroupService->reorderPackages($group, [$plan2->id, $plan1->id]);

        $this->assertDatabaseHas('package_group_items', [
            'package_group_id' => $group->id,
            'subscription_plan_id' => $plan2->id,
            'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('package_group_items', [
            'package_group_id' => $group->id,
            'subscription_plan_id' => $plan1->id,
            'sort_order' => 1,
        ]);
    }

    public function test_get_active_groups_returns_only_active(): void
    {
        PackageGroup::create([
            'name' => 'Active Group',
            'is_active' => true,
        ]);

        PackageGroup::create([
            'name' => 'Inactive Group',
            'is_active' => false,
        ]);

        $groups = $this->packageGroupService->getActiveGroups();

        $this->assertCount(1, $groups);
        $this->assertEquals('Active Group', $groups->first()->name);
    }

    public function test_active_group_includes_packages(): void
    {
        $group = PackageGroup::create([
            'name' => 'Web Hosting',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Basic Plan',
            'code' => 'basic',
            'price' => 5.00,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $group->packages()->attach($plan->id, ['sort_order' => 0]);

        $groups = $this->packageGroupService->getActiveGroups();

        $this->assertCount(1, $groups->first()->packages);
        $this->assertEquals('Basic Plan', $groups->first()->packages->first()->name);
    }

    public function test_get_active_groups_orders_by_sort_order(): void
    {
        PackageGroup::create(['name' => 'Second', 'sort_order' => 2, 'is_active' => true]);
        PackageGroup::create(['name' => 'First', 'sort_order' => 1, 'is_active' => true]);

        $groups = $this->packageGroupService->getActiveGroups();

        $this->assertEquals(['First', 'Second'], $groups->pluck('name')->all());
    }

    public function test_get_active_groups_filters_by_team(): void
    {
        $teamId = User::factory()->withPersonalTeam()->create()->currentTeam->id;

        PackageGroup::create(['name' => 'Team Group', 'team_id' => $teamId, 'is_active' => true]);
        PackageGroup::create(['name' => 'Other Group', 'team_id' => null, 'is_active' => true]);

        $groups = $this->packageGroupService->getActiveGroups($teamId);

        $this->assertCount(1, $groups);
        $this->assertEquals('Team Group', $groups->first()->name);
    }

    public function test_active_group_excludes_inactive_packages(): void
    {
        $group = PackageGroup::create(['name' => 'Web Hosting', 'is_active' => true]);

        $active = SubscriptionPlan::create([
            'name' => 'Active Plan', 'code' => 'active', 'price' => 5.00, 'currency' => 'USD', 'is_active' => true,
        ]);
        $inactive = SubscriptionPlan::create([
            'name' => 'Inactive Plan', 'code' => 'inactive', 'price' => 5.00, 'currency' => 'USD', 'is_active' => false,
        ]);

        $group->packages()->attach($active->id, ['sort_order' => 0]);
        $group->packages()->attach($inactive->id, ['sort_order' => 1]);

        $packages = $this->packageGroupService->getActiveGroups()->first()->packages;

        $this->assertCount(1, $packages);
        $this->assertEquals('Active Plan', $packages->first()->name);
    }

    public function test_packages_returned_ordered_by_pivot_sort_order_after_reorder(): void
    {
        $group = PackageGroup::create(['name' => 'Web Hosting', 'is_active' => true]);

        $planA = SubscriptionPlan::create([
            'name' => 'Plan A', 'code' => 'plan-a', 'price' => 9.99, 'currency' => 'USD', 'is_active' => true,
        ]);
        $planB = SubscriptionPlan::create([
            'name' => 'Plan B', 'code' => 'plan-b', 'price' => 19.99, 'currency' => 'USD', 'is_active' => true,
        ]);

        $this->packageGroupService->addPackage($group, $planA, 0);
        $this->packageGroupService->addPackage($group, $planB, 1);

        $this->packageGroupService->reorderPackages($group, [$planB->id, $planA->id]);

        $names = $group->fresh()->packages->pluck('name')->all();

        $this->assertEquals(['Plan B', 'Plan A'], $names);
    }

    public function test_add_package_is_idempotent(): void
    {
        $group = PackageGroup::create(['name' => 'Web Hosting', 'is_active' => true]);
        $plan = SubscriptionPlan::create([
            'name' => 'Starter', 'code' => 'starter', 'price' => 9.99, 'currency' => 'USD', 'is_active' => true,
        ]);

        $this->packageGroupService->addPackage($group, $plan, 0);
        $this->packageGroupService->addPackage($group, $plan, 5);

        $this->assertCount(1, $group->fresh()->packages);
    }
}
