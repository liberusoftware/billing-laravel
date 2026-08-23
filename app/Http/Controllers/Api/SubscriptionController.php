<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(
            Subscription::class,
            'subscription'
        );
    }

    public function index(Request $request): JsonResponse
    {
        $subscriptions = Subscription::query()
            ->where('team_id', $this->currentTeamId($request))
            ->when(
                $request->customer_id,
                fn ($q) => $q->where(
                    'customer_id',
                    $request->customer_id
                )
            )
            ->when(
                $request->status,
                fn ($q) => $q->where(
                    'status',
                    $request->status
                )
            )
            ->paginate(15);

        return response()->json($subscriptions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                'customer_id' => ['required', Rule::exists('customers', 'id')->where('team_id', $this->currentTeamId($request))],
                'product_service_id' => ['required', Rule::exists('products_services', 'id')->where('team_id', $this->currentTeamId($request))],
                'start_date' => 'required|date',
                'end_date' => 'nullable|date',
                'renewal_period' => 'required|string',
                'price' => 'required|numeric|min:0',
                'currency' => 'required|string|size:3',
                'auto_renew' => 'boolean',
            ]
        );

        $subscription = new Subscription($validated);
        $subscription->team_id = $this->currentTeamId($request);
        $subscription->save();

        return response()->json(
            $subscription,
            201
        );
    }

    public function show(Request $request, Subscription $subscription): JsonResponse
    {
        $this->assertSameTeam($request, $subscription);

        return response()->json($subscription);
    }

    public function update(Request $request, Subscription $subscription): JsonResponse
    {
        $this->assertSameTeam($request, $subscription);

        $validated = $request->validate(
            [
                'end_date' => 'nullable|date',
                'renewal_period' => 'string',
                'price' => 'numeric|min:0',
                'currency' => 'string|size:3',
                'auto_renew' => 'boolean',
                'status' => 'string|in:active,cancelled,suspended,expired',
            ]
        );

        $subscription->update($validated);

        return response()->json($subscription);
    }

    public function destroy(Request $request, Subscription $subscription): JsonResponse
    {
        $this->assertSameTeam($request, $subscription);
        $subscription->delete();

        return response()->json(
            null,
            204
        );
    }

    public function cancel(Request $request, Subscription $subscription): JsonResponse
    {
        $this->assertSameTeam($request, $subscription);
        $subscription->update(['status' => 'cancelled']);

        return response()->json($subscription);
    }

    public function renew(Request $request, Subscription $subscription): JsonResponse
    {
        $this->assertSameTeam($request, $subscription);
        $subscription->update(['status' => 'active']);

        return response()->json($subscription);
    }

    /** Block cross-tenant access: another team's subscription is treated as not found. */
    private function assertSameTeam(Request $request, Subscription $subscription): void
    {
        abort_unless($subscription->team_id === $this->currentTeamId($request), 404);
    }

    private function currentTeamId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user?->current_team_id;
    }
}
