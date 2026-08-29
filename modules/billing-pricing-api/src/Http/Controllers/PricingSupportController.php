<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Pricing\Actions\CalculateProration;
use Liberu\Billing\Pricing\Actions\CapturePricingSnapshot;
use Liberu\Billing\Pricing\Actions\RedeemPricingDiscount;
use Liberu\Billing\Pricing\Models\PricingContract;
use Liberu\Billing\Pricing\Models\PricingDiscount;
use Liberu\Billing\Pricing\Models\PricingPlan;
use Liberu\Billing\Pricing\Models\PricingSnapshot;

final class PricingSupportController extends Controller
{
    public function contracts(Request $request): JsonResponse
    {
        return $this->list($request, PricingContract::class);
    }

    public function discounts(Request $request): JsonResponse
    {
        return $this->list($request, PricingDiscount::class);
    }

    public function snapshots(Request $request): JsonResponse
    {
        return $this->list($request, PricingSnapshot::class);
    }

    public function redeem(Request $request, PricingDiscount $discount, RedeemPricingDiscount $redeem): JsonResponse
    {
        $discount = $this->forCurrentTeam($request, PricingDiscount::class, $discount->getKey());
        Gate::authorize('update', $discount);

        return response()->json(['data' => $redeem->execute($discount)]);
    }

    public function snapshot(Request $request, PricingPlan $plan, CapturePricingSnapshot $capture): JsonResponse
    {
        $plan = $this->forCurrentTeam($request, PricingPlan::class, $plan->getKey());
        Gate::authorize('update', $plan);

        return response()->json(['data' => $capture->execute($plan)], 201);
    }

    public function proration(Request $request, CalculateProration $calculate): JsonResponse
    {
        $data = $request->validate(['amount_minor' => ['required', 'integer', 'min:0'], 'remaining_days' => ['required', 'integer', 'min:0'], 'period_days' => ['required', 'integer', 'min:1']]);

        return response()->json(['data' => ['amount_minor' => $calculate->execute($data['amount_minor'], $data['remaining_days'], $data['period_days'])]]);
    }

    private function list(Request $request, string $model): JsonResponse
    {
        Gate::authorize('viewAny', $model);

        return response()->json($model::query()->where('team_id', $this->team($request))->latest()->paginate($request->integer('per_page', 25)));
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }

    /** @template TModel of object */
    private function forCurrentTeam(Request $request, string $model, int $id): object
    {
        $teamId = $this->team($request);

        return $model::query()->whereKey($id)->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $teamId))->firstOrFail();
    }
}
