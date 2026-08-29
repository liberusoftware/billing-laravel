<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Liberu\Billing\Pricing\Models\PricingContract;
use Liberu\Billing\Pricing\Support\CustomerReference;

final readonly class CreatePricingContract
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): PricingContract
    {
        $teamId = $attributes['team_id'] ?? null;
        $customerId = CustomerReference::assertBelongsToTeam($this->database, $attributes['customer_id'] ?? null, $teamId);
        $planId = $attributes['pricing_plan_id'] ?? null;

        if ($planId === null || ! Schema::hasTable('billing_pricing_plans')) {
            throw new \InvalidArgumentException('Pricing plan reference is invalid.');
        }

        $plan = $this->database->table('billing_pricing_plans')->where('id', (int) $planId)->first(['team_id']);
        if ($plan === null || ($plan->team_id !== null && ($teamId === null || (int) $plan->team_id !== (int) $teamId))) {
            throw new \InvalidArgumentException('Pricing plan reference is invalid.');
        }

        return $this->database->transaction(fn (): PricingContract => PricingContract::query()->create([
            'team_id' => $teamId,
            'pricing_plan_id' => (int) $planId,
            'customer_id' => $customerId,
            'starts_at' => $attributes['starts_at'] ?? now(),
            'ends_at' => $attributes['ends_at'] ?? null,
            'terms' => $attributes['terms'] ?? [],
            'status' => $attributes['status'] ?? 'active',
        ]));
    }
}
