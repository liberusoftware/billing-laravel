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
use Liberu\Billing\Usage\Queries\ListMeters;
use Liberu\Billing\Usage\Queries\ListUsageRecords;

final class UsageController extends Controller
{
    public function meters(Request $request, ListMeters $meters): JsonResponse
    {
        Gate::authorize('viewAny', Meter::class);

        $team = $this->team($request);
        $result = Meter::query()
            ->where(fn ($query) => $team === 0
                ? $query->whereNull('team_id')
                : $query->whereNull('team_id')->orWhere('team_id', $team))
            ->latest('id')
            ->paginate($request->integer('per_page', 25));

        return response()->json(['data' => $result->getCollection()->map(fn (Meter $meter): array => $this->meter($meter))->values(), 'meta' => ['current_page' => $result->currentPage(), 'last_page' => $result->lastPage()]]);
    }

    public function storeMeter(Request $request, DefineMeter $define): JsonResponse
    {
        return $this->defineMeter($request, $define);
    }

    public function defineMeter(Request $request, DefineMeter $define): JsonResponse
    {
        Gate::authorize('create', Meter::class);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'], 'code' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'unit' => ['required', 'string', 'max:50'], 'unit_price_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'alpha'], 'threshold' => ['nullable', 'numeric', 'min:0'], 'metadata' => ['sometimes', 'array'],
        ]);
        $data['team_id'] = $this->team($request);

        return response()->json(['data' => $this->meter($define->execute($data))], 201);
    }

    public function ingest(Request $request, Meter $meter, IngestUsage $ingest): JsonResponse
    {
        Gate::authorize('create', UsageRecord::class);
        abort_unless($meter->team_id === null || $meter->team_id === $this->team($request), 404);
        $data = $request->validate(['event_key' => ['required', 'string', 'max:255'], 'customer_id' => ['nullable', 'integer'], 'quantity' => ['required', 'numeric', 'gt:0'], 'occurred_at' => ['nullable', 'date'], 'metadata' => ['sometimes', 'array']]);

        return response()->json(['data' => $this->record($ingest->execute($meter, $data))], 201);
    }

    public function records(Request $request, ListUsageRecords $records): JsonResponse
    {
        Gate::authorize('viewAny', UsageRecord::class);

        return response()->json(['data' => $records->execute($this->team($request), $request->integer('meter_id'))->map(fn (UsageRecord $record): array => $this->record($record))->values()]);
    }

    public function aggregate(Request $request, AggregateUsage $aggregate): JsonResponse
    {
        Gate::authorize('viewAny', UsageRecord::class);
        $data = $request->validate(['meter_id' => ['required', 'integer', 'exists:billing_usage_meters,id'], 'customer_id' => ['nullable', 'integer']]);
        $meter = Meter::query()->whereKey($data['meter_id'])->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $this->team($request)))->firstOrFail();
        abort_unless($meter->team_id === null || $meter->team_id === $this->team($request), 404);

        return response()->json(['data' => $aggregate->execute((int) $data['meter_id'], $data['customer_id'] ?? null)]);
    }

    public function aggregateForMeter(Request $request, Meter $meter, AggregateUsage $aggregate): JsonResponse
    {
        $meter = $this->forCurrentTeam($request, $meter->getKey());
        Gate::authorize('view', $meter);

        return response()->json(['data' => $aggregate->execute((int) $meter->getKey(), $request->integer('customer_id') ?: null)]);
    }

    public function rate(Request $request, Meter $meter, RateUsage $rate, CheckUsageThreshold $threshold): JsonResponse
    {
        $meter = $this->forCurrentTeam($request, $meter->getKey());
        Gate::authorize('view', $meter);
        $data = $request->validate(['quantity' => ['required', 'numeric', 'min:0']]);
        $quantity = (float) $data['quantity'];

        return response()->json(['data' => ['amount_minor' => $rate->execute($meter, $quantity), 'threshold_reached' => $threshold->execute($meter, $quantity)]]);
    }

    public function correct(Request $request, UsageRecord $record, CorrectUsage $correct): JsonResponse
    {
        Gate::authorize('create', UsageRecord::class);
        abort_unless($record->team_id === $this->team($request), 404);
        $data = $request->validate(['quantity' => ['required', 'numeric', 'not_in:0'], 'event_key' => ['required', 'string', 'max:255']]);

        return response()->json(['data' => $this->record($correct->execute($record, (float) $data['quantity'], $data['event_key']))], 201);
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }

    private function forCurrentTeam(Request $request, int $meterId): Meter
    {
        $teamId = $this->team($request);

        return Meter::query()->whereKey($meterId)->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $teamId))->firstOrFail();
    }

    private function meter(Meter $meter): array
    {
        return ['id' => (string) $meter->getKey(), 'type' => 'billing-usage-meter', 'attributes' => $meter->only(['name', 'code', 'unit', 'unit_price_minor', 'currency', 'threshold', 'active', 'metadata'])];
    }

    private function record(UsageRecord $record): array
    {
        return ['id' => (string) $record->getKey(), 'type' => 'billing-usage-record', 'attributes' => $record->only(['meter_id', 'customer_id', 'event_key', 'quantity', 'unit_price_minor', 'amount_minor', 'currency', 'occurred_at', 'corrects_id', 'metadata'])];
    }
}
