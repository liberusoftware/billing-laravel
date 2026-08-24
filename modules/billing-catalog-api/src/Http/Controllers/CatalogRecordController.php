<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Catalog\Actions\CreateCatalogRecord;
use Liberu\Billing\Catalog\Actions\TransitionCatalogLifecycle;
use Liberu\Billing\Catalog\Enums\CatalogStatus;
use Liberu\Billing\Catalog\Models\Addon;
use Liberu\Billing\Catalog\Models\Bundle;
use Liberu\Billing\Catalog\Models\Channel;
use Liberu\Billing\Catalog\Models\ConfigurableOption;
use Liberu\Billing\Catalog\Models\Eligibility;
use Liberu\Billing\Catalog\Models\Plan;
use Liberu\Billing\Catalog\Queries\ListCatalogRecords;

final class CatalogRecordController extends Controller
{
    public function index(string $type, Request $request, ListCatalogRecords $query): JsonResponse
    {
        $model = $this->model($type);
        Gate::authorize('viewAny', $model);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $records = $query->execute($model, $teamId !== null ? (int) $teamId : null, $request->integer('per_page', 25));

        return response()->json(['data' => $records->getCollection()->map(fn ($record): array => $this->resource($record, $type))->values(), 'meta' => ['current_page' => $records->currentPage(), 'last_page' => $records->lastPage()]]);
    }

    public function store(string $type, Request $request, CreateCatalogRecord $create): JsonResponse
    {
        $model = $this->model($type);
        Gate::authorize('create', $model);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string'], 'configuration' => ['sometimes', 'array']]);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $record = $create->execute($model, [...$data, 'team_id' => $teamId !== null ? (int) $teamId : null]);

        return response()->json(['data' => $this->resource($record, $type)], 201);
    }

    public function transition(string $type, int $record, Request $request, TransitionCatalogLifecycle $transition): JsonResponse
    {
        $model = $this->model($type);
        $instance = $model::query()->findOrFail($record);
        Gate::authorize('update', $instance);
        $data = $request->validate(['status' => ['required', 'string']]);
        $updated = $transition->execute($instance, CatalogStatus::from($data['status']));

        return response()->json(['data' => $this->resource($updated, $type)]);
    }

    private function model(string $type): string
    {
        return ['plans' => Plan::class, 'addons' => Addon::class, 'bundles' => Bundle::class, 'options' => ConfigurableOption::class, 'eligibility' => Eligibility::class, 'channels' => Channel::class][$type] ?? abort(404);
    }

    private function resource(object $record, string $type): array
    {
        return ['id' => (string) $record->getKey(), 'type' => "billing-catalog-{$type}", 'attributes' => ['name' => $record->name, 'code' => $record->code, 'description' => $record->description, 'status' => $record->status->value, 'configuration' => $record->configuration ?? []]];
    }
}
