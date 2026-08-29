<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Api\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Subscriptions\Actions\ActivateSubscription;
use Liberu\Billing\Subscriptions\Actions\CancelSubscription;
use Liberu\Billing\Subscriptions\Actions\ChangeSubscriptionPlan;
use Liberu\Billing\Subscriptions\Actions\ExpireSubscriptions;
use Liberu\Billing\Subscriptions\Actions\PauseSubscription;
use Liberu\Billing\Subscriptions\Actions\RenewSubscription;
use Liberu\Billing\Subscriptions\Actions\ResumeSubscription;
use Liberu\Billing\Subscriptions\Actions\UpdateEntitlementState;
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
            'id_protection' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'array'],
        ]);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return response()->json(['data' => $this->resource($activate->execute($data))], 201);
    }

    public function renew(Request $request, Subscription $subscription, RenewSubscription $renew): JsonResponse
    {
        $subscription = $this->forCurrentTeam($request, $subscription);
        Gate::authorize('update', $subscription);

        return response()->json(['data' => $this->resource($renew->execute($subscription))]);
    }

    public function pause(Request $request, Subscription $subscription, PauseSubscription $pause): JsonResponse
    {
        $subscription = $this->forCurrentTeam($request, $subscription);
        Gate::authorize('update', $subscription);

        return response()->json(['data' => $this->resource($pause->execute($subscription))]);
    }

    public function cancel(Request $request, Subscription $subscription, CancelSubscription $cancel): JsonResponse
    {
        $subscription = $this->forCurrentTeam($request, $subscription);
        Gate::authorize('update', $subscription);

        return response()->json(['data' => $this->resource($cancel->execute($subscription))]);
    }

    public function resume(Request $request, Subscription $subscription, ResumeSubscription $resume): JsonResponse
    {
        $subscription = $this->forCurrentTeam($request, $subscription);
        Gate::authorize('update', $subscription);

        return response()->json(['data' => $this->resource($resume->execute($subscription))]);
    }

    public function changePlan(Request $request, Subscription $subscription, ChangeSubscriptionPlan $change): JsonResponse
    {
        $subscription = $this->forCurrentTeam($request, $subscription);
        Gate::authorize('update', $subscription);
        $data = $request->validate(['pricing_plan_id' => ['nullable', 'integer', 'min:1']]);

        return response()->json(['data' => $this->resource($change->execute($subscription, $data['pricing_plan_id'] ?? null))]);
    }

    public function entitlements(Request $request, Subscription $subscription, UpdateEntitlementState $update): JsonResponse
    {
        $subscription = $this->forCurrentTeam($request, $subscription);
        Gate::authorize('update', $subscription);
        $data = $request->validate(['entitlement_state' => ['required', 'array']]);

        return response()->json(['data' => $this->resource($update->execute($subscription, $data['entitlement_state']))]);
    }

    public function expire(Request $request, ExpireSubscriptions $expire): JsonResponse
    {
        Gate::authorize('create', Subscription::class);
        $data = $request->validate(['as_of' => ['nullable', 'date']]);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');

        return response()->json(['data' => ['expired' => $expire->execute($teamId === null ? null : (int) $teamId, isset($data['as_of']) ? Carbon::parse($data['as_of']) : null)]]);
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
                'id_protection' => $subscription->id_protection,
                'entitlement_state' => $subscription->entitlement_state ?? [],
            ],
        ];
    }

    private function forCurrentTeam(Request $request, Subscription $subscription): Subscription
    {
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');

        return Subscription::query()->whereKey($subscription->getKey())->where('team_id', $teamId)->firstOrFail();
    }
}
