<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Subscriptions\Actions\ActivateSubscription;
use Liberu\Billing\Subscriptions\Actions\CancelSubscription;
use Liberu\Billing\Subscriptions\Actions\PauseSubscription;
use Liberu\Billing\Subscriptions\Actions\RenewSubscription;
use Liberu\Billing\Subscriptions\Models\Subscription;
use Liberu\Billing\Subscriptions\Queries\ListSubscriptions;

final class SubscriptionController extends Controller
{
    public function index(Request $request, ListSubscriptions $query): JsonResponse
    {
        Gate::authorize('viewAny', Subscription::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $subscriptions = $query->execute($teamId === null ? null : (int) $teamId, $request->integer('per_page', 25));

        return response()->json([
            'data' => $subscriptions->getCollection()->map(fn (Subscription $subscription): array => $this->resource($subscription))->values(),
            'meta' => ['current_page' => $subscriptions->currentPage(), 'last_page' => $subscriptions->lastPage()],
        ]);
    }

    public function store(Request $request, ActivateSubscription $activate): JsonResponse
    {
        Gate::authorize('create', Subscription::class);
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'pricing_plan_id' => ['nullable', 'integer', 'min:1'],
            'trial_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'current_period_ends_at' => ['nullable', 'date'],
            'auto_renew' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'array'],
        ]);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return response()->json(['data' => $this->resource($activate->execute($data))], 201);
    }

    public function renew(Subscription $subscription, RenewSubscription $renew): JsonResponse
    {
        Gate::authorize('update', $subscription);

        return response()->json(['data' => $this->resource($renew->execute($subscription))]);
    }

    public function pause(Subscription $subscription, PauseSubscription $pause): JsonResponse
    {
        Gate::authorize('update', $subscription);

        return response()->json(['data' => $this->resource($pause->execute($subscription))]);
    }

    public function cancel(Subscription $subscription, CancelSubscription $cancel): JsonResponse
    {
        Gate::authorize('update', $subscription);

        return response()->json(['data' => $this->resource($cancel->execute($subscription))]);
    }

    private function resource(Subscription $subscription): array
    {
        return [
            'id' => (string) $subscription->getKey(),
            'type' => 'billing-subscriptions',
            'attributes' => [
                'status' => $subscription->status->value,
                'pricing_plan_id' => $subscription->pricing_plan_id,
                'starts_at' => $subscription->starts_at?->toIso8601String(),
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'current_period_ends_at' => $subscription->current_period_ends_at?->toIso8601String(),
                'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
                'paused_at' => $subscription->paused_at?->toIso8601String(),
                'auto_renew' => $subscription->auto_renew,
                'entitlement_state' => $subscription->entitlement_state ?? [],
            ],
        ];
    }
}
