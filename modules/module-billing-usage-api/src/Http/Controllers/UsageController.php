<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Usage\Actions\CheckUsageThreshold;
use Liberu\Billing\Usage\Actions\CorrectUsage;
use Liberu\Billing\Usage\Actions\DefineMeter;
use Liberu\Billing\Usage\Actions\IngestUsage;
use Liberu\Billing\Usage\Actions\RateUsage;
use Liberu\Billing\Usage\Models\Meter;
use Liberu\Billing\Usage\Models\UsageRecord;
use Liberu\Billing\Usage\Queries\AggregateUsage;

final class UsageController extends Controller
{
    public function meters(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Meter::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $meters = Meter::query()->when($teamId !== null, fn ($query) => $query->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', (int) $teamId)))->latest('id')->paginate($request->integer('per_page', 25));

        return response()->json(['data' => $meters->getCollection()->map(fn (Meter $meter): array => $this->meter($meter))->values(), 'meta' => ['current_page' => $meters->currentPage(), 'last_page' => $meters->lastPage()]]);
    }

    public function storeMeter(Request $request, DefineMeter $define): JsonResponse
    {
        Gate::authorize('create', Meter::class);
        $data = $request->validate(['name' => ['nullable', 'string', 'max:255'], 'code' => ['required', 'string', 'max:100'], 'unit' => ['required', 'string', 'max:50'], 'unit_price_minor' => ['required', 'integer', 'min:0'], 'currency' => ['required', 'string', 'size:3', 'alpha'], 'threshold' => ['nullable', 'numeric', 'min:0'], 'metadata' => ['sometimes', 'array']]);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return response()->json(['data' => $this->meter($define->execute($data))], 201);
    }

    public function ingest(Request $request, Meter $meter, IngestUsage $ingest): JsonResponse
    {
        Gate::authorize('update', $meter);
        $data = $request->validate(['event_key' => ['required', 'string', 'max:255'], 'customer_id' => ['nullable', 'integer', 'min:1'], 'quantity' => ['required', 'numeric', 'gt:0'], 'occurred_at' => ['sometimes', 'date'], 'metadata' => ['sometimes', 'array']]);

        return response()->json(['data' => $this->record($ingest->execute($meter, $data))], 201);
    }

    public function correct(Request $request, UsageRecord $record, CorrectUsage $correct): JsonResponse
    {
        Gate::authorize('create', Meter::class);
        $data = $request->validate(['quantity' => ['required', 'numeric', 'not_in:0'], 'event_key' => ['required', 'string', 'max:255']]);

        return response()->json(['data' => $this->record($correct->execute($record, (float) $data['quantity'], $data['event_key']))], 201);
    }

    public function aggregate(Request $request, Meter $meter, AggregateUsage $aggregate): JsonResponse
    {
        Gate::authorize('view', $meter);

        return response()->json(['data' => $aggregate->execute($meter->id, $request->integer('customer_id') ?: null)]);
    }

    public function rate(Request $request, Meter $meter, RateUsage $rate, CheckUsageThreshold $threshold): JsonResponse
    {
        Gate::authorize('view', $meter);
        $data = $request->validate(['quantity' => ['required', 'numeric', 'min:0']]);
        $quantity = (float) $data['quantity'];

        return response()->json(['data' => ['amount_minor' => $rate->execute($meter, $quantity), 'threshold_reached' => $threshold->execute($meter, $quantity)]]);
    }

    private function meter(Meter $meter): array
    {
        return ['id' => (string) $meter->getKey(), 'type' => 'billing-usage-meters', 'attributes' => ['name' => $meter->name, 'code' => $meter->code, 'unit' => $meter->unit, 'unit_price_minor' => $meter->unit_price_minor, 'currency' => $meter->currency, 'threshold' => $meter->threshold, 'active' => $meter->active]];
    }

    private function record(UsageRecord $record): array
    {
        return ['id' => (string) $record->getKey(), 'type' => 'billing-usage-records', 'attributes' => ['meter_id' => $record->meter_id, 'customer_id' => $record->customer_id, 'event_key' => $record->event_key, 'quantity' => $record->quantity, 'amount_minor' => $record->amount_minor, 'currency' => $record->currency, 'occurred_at' => $record->occurred_at?->toIso8601String(), 'corrects_id' => $record->corrects_id]];
    }
}
