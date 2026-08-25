<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Pricing\Actions\CreatePricingPlan;
use Liberu\Billing\Pricing\Models\PricingPlan;
use Liberu\Billing\Pricing\Queries\ListPricingPlans;

final class PricingPlanController extends Controller
{
    public function index(Request $request, ListPricingPlans $query): JsonResponse
    {
        Gate::authorize('viewAny', PricingPlan::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $plans = $query->execute($teamId === null ? null : (int) $teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $plans->getCollection()->map(fn (PricingPlan $plan): array => $this->resource($plan))->values(), 'meta' => ['current_page' => $plans->currentPage(), 'last_page' => $plans->lastPage()]]);
    }

    public function store(Request $request, CreatePricingPlan $create): JsonResponse
    {
        Gate::authorize('create', PricingPlan::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'pricing_model' => ['required', 'in:recurring,one_time,usage,tiered'], 'currency' => ['required', 'string', 'size:3', 'alpha'], 'unit_amount_minor' => ['required', 'integer', 'min:0'], 'billing_interval' => ['nullable', 'string', 'max:30'], 'usage_unit' => ['nullable', 'string', 'max:50'], 'tiers' => ['nullable', 'array'], 'product_id' => ['nullable', 'integer'], 'metadata' => ['sometimes', 'array']]);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return response()->json(['data' => $this->resource($create->execute($data))], 201);
    }

    private function resource(PricingPlan $plan): array
    {
        return ['id' => (string) $plan->getKey(), 'type' => 'billing-pricing-plan', 'attributes' => ['name' => $plan->name, 'pricing_model' => $plan->pricing_model->value, 'currency' => $plan->currency, 'unit_amount_minor' => $plan->unit_amount_minor, 'billing_interval' => $plan->billing_interval, 'usage_unit' => $plan->usage_unit, 'tiers' => $plan->tiers ?? [], 'status' => $plan->status->value, 'metadata' => $plan->metadata ?? []]];
    }
}
