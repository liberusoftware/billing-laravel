<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Collections\Actions\ApplyCreditControl;
use Liberu\Billing\Collections\Actions\OpenCollectionCase;
use Liberu\Billing\Collections\Actions\PromisePayment;
use Liberu\Billing\Collections\Actions\RecoverCollectionCase;
use Liberu\Billing\Collections\Actions\RetryCollectionCase;
use Liberu\Billing\Collections\Actions\ScheduleDunning;
use Liberu\Billing\Collections\Actions\ScheduleReminder;
use Liberu\Billing\Collections\Actions\SuspendCollectionCase;
use Liberu\Billing\Collections\Actions\WriteOffCollectionCase;
use Liberu\Billing\Collections\Models\CollectionCase;
use Liberu\Billing\Collections\Queries\ListCollectionCases;

final class CollectionCaseController extends Controller
{
    public function index(Request $request, ListCollectionCases $query): JsonResponse
    {
        Gate::authorize('viewAny', CollectionCase::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $cases = $query->execute($teamId === null ? null : (int) $teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $cases->getCollection()->map(fn (CollectionCase $case): array => $this->resource($case))->values(), 'meta' => ['current_page' => $cases->currentPage(), 'last_page' => $cases->lastPage()]]);
    }

    public function store(Request $request, OpenCollectionCase $open): JsonResponse
    {
        Gate::authorize('create', CollectionCase::class);
        $data = $request->validate(['customer_id' => ['nullable', 'integer', 'min:1'], 'invoice_id' => ['nullable', 'integer', 'min:1'], 'type' => ['sometimes', 'string', 'max:100'], 'amount_minor' => ['required', 'integer', 'min:1'], 'currency' => ['required', 'string', 'size:3', 'alpha'], 'reason' => ['nullable', 'string', 'max:1000'], 'metadata' => ['sometimes', 'array']]);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return response()->json(['data' => $this->resource($open->execute($data))], 201);
    }

    public function promise(Request $request, CollectionCase $case, PromisePayment $promise): JsonResponse
    {
        Gate::authorize('update', $case);
        $data = $request->validate(['due_at' => ['required', 'date', 'after:now']]);

        return response()->json(['data' => $this->resource($promise->execute($case, new \DateTimeImmutable($data['due_at'])))]);
    }

    public function suspend(Request $request, CollectionCase $case, SuspendCollectionCase $suspend): JsonResponse
    {
        Gate::authorize('update', $case);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        return response()->json(['data' => $this->resource($suspend->execute($case, $data['reason']))]);
    }

    public function recover(CollectionCase $case, RecoverCollectionCase $recover): JsonResponse
    {
        Gate::authorize('update', $case);

        return response()->json(['data' => $this->resource($recover->execute($case))]);
    }

    public function retry(Request $request, CollectionCase $case, RetryCollectionCase $retry): JsonResponse
    {
        Gate::authorize('update', $case);
        $data = $request->validate(['next_action_at' => ['required', 'date', 'after:now']]);

        return response()->json(['data' => $this->resource($retry->execute($case, new \DateTimeImmutable($data['next_action_at'])))]);
    }

    public function writeOff(Request $request, CollectionCase $case, WriteOffCollectionCase $writeOff): JsonResponse
    {
        Gate::authorize('update', $case);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        return response()->json(['data' => $this->resource($writeOff->execute($case, $data['reason']))]);
    }

    public function dunning(Request $request, CollectionCase $case, ScheduleDunning $schedule): JsonResponse
    {
        Gate::authorize('update', $case);
        $data = $request->validate(['next_action_at' => ['required', 'date', 'after:now']]);

        return response()->json(['data' => $this->resource($schedule->execute($case, new \DateTimeImmutable($data['next_action_at'])))]);
    }

    public function reminder(Request $request, CollectionCase $case, ScheduleReminder $schedule): JsonResponse
    {
        Gate::authorize('update', $case);
        $data = $request->validate(['next_action_at' => ['required', 'date', 'after:now']]);

        return response()->json(['data' => $this->resource($schedule->execute($case, new \DateTimeImmutable($data['next_action_at'])))]);
    }

    public function creditControl(Request $request, CollectionCase $case, ApplyCreditControl $apply): JsonResponse
    {
        Gate::authorize('update', $case);
        $data = $request->validate(['level' => ['required', 'string', 'max:50'], 'reason' => ['nullable', 'string', 'max:1000']]);

        return response()->json(['data' => $this->resource($apply->execute($case, $data['level'], $data['reason'] ?? null))]);
    }

    private function resource(CollectionCase $case): array
    {
        return ['id' => (string) $case->getKey(), 'type' => 'billing-collection-cases', 'attributes' => ['type' => $case->type, 'status' => $case->status->value, 'amount_minor' => $case->amount_minor, 'currency' => $case->currency, 'next_action_at' => $case->next_action_at?->toIso8601String(), 'promise_due_at' => $case->promise_due_at?->toIso8601String(), 'reason' => $case->reason]];
    }
}
