<?php

namespace App\Services;

use App\Models\PackageGroup;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PackageGroupService
{
    /**
     * Get all active package groups with their packages
     */
    public function getActiveGroups(?int $teamId = null): Collection
    {
        $query = PackageGroup::query()
            ->where(
                'is_active',
                true
            )
            ->with(
                [
                    'packages' => fn ($q) => $q->where(
                        'is_active',
                        true
                    ),
                ]
            )
            ->orderBy('sort_order');

        if ($teamId) {
            $query->where(
                'team_id',
                $teamId
            );
        }

        return $query->get();
    }

    /**
     * Create a new package group
     */
    public function createGroup(array $data): PackageGroup
    {
        return PackageGroup::create($data);
    }

    /**
     * Update an existing package group
     */
    public function updateGroup(PackageGroup $group, array $data): PackageGroup
    {
        $group->update($data);

        return $group->fresh(['packages']);
    }

    /**
     * Delete a package group
     */
    public function deleteGroup(PackageGroup $group): void
    {
        $group->delete();
    }

    /**
     * Add a subscription plan to a package group
     */
    public function addPackage(PackageGroup $group, SubscriptionPlan $plan, int $sortOrder = 0): void
    {
        $group->packages()->syncWithoutDetaching(
            [
                $plan->id => ['sort_order' => $sortOrder],
            ]
        );
    }

    /**
     * Remove a subscription plan from a package group
     */
    public function removePackage(PackageGroup $group, SubscriptionPlan $plan): void
    {
        $group->packages()->detach($plan->id);
    }

    /**
     * Reorder packages within a group
     */
    public function reorderPackages(PackageGroup $group, array $orderedPlanIds): void
    {
        DB::transaction(
            function () use ($group, $orderedPlanIds): void {
                foreach ($orderedPlanIds as $index => $planId) {
                    $group->packages()->updateExistingPivot(
                        $planId,
                        ['sort_order' => $index]
                    );
                }
            }
        );
    }
}
