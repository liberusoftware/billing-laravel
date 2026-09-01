<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Api\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Pricing\Actions\CalculateProration;
use Liberu\Billing\Pricing\Actions\CalculateUsageBasedPrice;
use Liberu\Billing\Pricing\Actions\CapturePricingSnapshot;
use Liberu\Billing\Pricing\Actions\CreatePricingContract;
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

    public function createContract(Request $request, CreatePricingContract $create): JsonResponse
    {
        Gate::authorize('create', PricingContract::class);
        $data = $request->validate([
            'pricing_plan_id' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'terms' => ['sometimes', 'array'],
            'status' => ['sometimes', 'string', 'in:active,ended,cancelled'],
        ]);
        $data['team_id'] = $this->team($request);

        return response()->json(['data' => $this->resource($create->execute($data))], 201);
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

        return response()->json(['data' => $this->resource($redeem->execute($discount))]);
    }

    public function snapshot(Request $request, PricingPlan $plan, CapturePricingSnapshot $capture): JsonResponse
    {
        $plan = $this->forCurrentTeam($request, PricingPlan::class, $plan->getKey());
        Gate::authorize('update', $plan);

        return response()->json(['data' => $this->resource($capture->execute($plan))], 201);
    }

    public function proration(Request $request, CalculateProration $calculate): JsonResponse
    {
        $data = $request->validate(['amount_minor' => ['required', 'integer', 'min:0'], 'remaining_days' => ['required', 'integer', 'min:0'], 'period_days' => ['required', 'integer', 'min:1']]);

        return response()->json(['data' => ['amount_minor' => $calculate->execute($data['amount_minor'], $data['remaining_days'], $data['period_days'])]]);
    }

    public function usage(Request $request, PricingPlan $plan, CalculateUsageBasedPrice $calculate): JsonResponse
    {
        $plan = $this->forCurrentTeam($request, PricingPlan::class, $plan->getKey());
        Gate::authorize('view', $plan);
        $data = $request->validate([
            'meter_id' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        return response()->json(['data' => $calculate->execute($plan, $data['meter_id'], Carbon::parse($data['start']), Carbon::parse($data['end']), $data['customer_id'] ?? null)]);
    }

    private function list(Request $request, string $model): JsonResponse
    {
        Gate::authorize('viewAny', $model);

        return $this->paginated($model::query()->where('team_id', $this->team($request))->latest()->paginate($this->pageSize($request)));
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }

    private function paginated(LengthAwarePaginator $results): JsonResponse
    {
        return response()->json(['data' => $results->getCollection()->map(fn (Model $model): array => $this->resource($model))->values(), 'links' => ['next' => $results->nextPageUrl(), 'prev' => $results->previousPageUrl()], 'meta' => ['current_page' => $results->currentPage(), 'last_page' => $results->lastPage(), 'per_page' => $results->perPage(), 'total' => $results->total()]]);
    }

    private function pageSize(Request $request): int
    {
        return min(max((int) $request->input('page.size', $request->integer('per_page', 25)), 1), 100);
    }

    private function resource(Model $model): array
    {
        $attributes = match (true) {
            $model instanceof PricingContract => $model->only(['team_id', 'pricing_plan_id', 'customer_id', 'starts_at', 'ends_at', 'terms', 'status', 'created_at', 'updated_at']),
            $model instanceof PricingDiscount => $model->only(['team_id', 'code', 'kind', 'value', 'currency', 'starts_at', 'ends_at', 'max_redemptions', 'redemptions', 'active', 'created_at', 'updated_at']),
            $model instanceof PricingSnapshot => $model->only(['team_id', 'pricing_plan_id', 'version', 'payload', 'captured_at', 'created_at', 'updated_at']),
            default => [],
        };

        return ['id' => (string) $model->getKey(), 'type' => str($model::class)->classBasename()->kebab()->toString(), 'attributes' => $attributes];
    }

    /** @template TModel of object */
    private function forCurrentTeam(Request $request, string $model, int $id): object
    {
        $teamId = $this->team($request);

        return $model::query()->whereKey($id)->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $teamId))->firstOrFail();
    }
}
