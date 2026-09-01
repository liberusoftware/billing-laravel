<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Api\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Provisioning\Actions\CreateProvisionedService;
use Liberu\Billing\Provisioning\Actions\QueueProvisioningOperation;
use Liberu\Billing\Provisioning\Actions\ReconcileProvisionedService;
use Liberu\Billing\Provisioning\Models\ProvisionedService;
use Liberu\Billing\Provisioning\Models\ProvisioningOperation;

final class ProvisioningOperationController extends Controller
{
    public function services(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ProvisionedService::class);

        return $this->paginated(ProvisionedService::query()->where('team_id', $this->team($request))->latest()->paginate($this->pageSize($request)));
    }

    public function showService(Request $request, int $provisionedService): JsonResponse
    {
        $service = ProvisionedService::query()->where('team_id', $this->team($request))->findOrFail($provisionedService);
        Gate::authorize('view', $service);

        return response()->json(['data' => $this->resource($service)]);
    }

    public function storeService(Request $request, CreateProvisionedService $create): JsonResponse
    {
        Gate::authorize('create', ProvisionedService::class);
        $data = $request->validate(['customer_id' => ['nullable', 'integer', 'min:1'], 'subscription_id' => ['nullable', 'integer', 'min:1'], 'provider' => ['required', 'string', 'max:100'], 'external_id' => ['nullable', 'string', 'max:255'], 'metadata' => ['sometimes', 'array']]);
        $data['team_id'] = $this->team($request);

        return response()->json(['data' => $this->resource($create->execute($data))], 201);
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ProvisioningOperation::class);

        return $this->paginated(ProvisioningOperation::query()->where('team_id', $this->team($request))->latest()->paginate($this->pageSize($request)));
    }

    public function queue(Request $request, ProvisionedService $provisionedService, QueueProvisioningOperation $queue): JsonResponse
    {
        $provisionedService = $this->forCurrentTeam($request, $provisionedService);
        Gate::authorize('update', $provisionedService);
        $data = $request->validate(['operation' => ['required', 'in:provision,deprovision,poll,reconcile,rollback'], 'payload' => ['sometimes', 'array']]);

        return response()->json(['data' => $this->resource($queue->execute($provisionedService, $data['operation'], $data['payload'] ?? []))], 202);
    }

    public function reconcile(Request $request, ProvisionedService $provisionedService, ReconcileProvisionedService $reconcile): JsonResponse
    {
        $provisionedService = $this->forCurrentTeam($request, $provisionedService);
        Gate::authorize('update', $provisionedService);

        return response()->json(['data' => $this->resource($reconcile->execute($provisionedService))]);
    }

    private function team(Request $request): int
    {
        $team = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }

    private function forCurrentTeam(Request $request, ProvisionedService $service): ProvisionedService
    {
        return ProvisionedService::query()->whereKey($service->getKey())->where('team_id', $this->team($request))->firstOrFail();
    }

    private function paginated(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json(['data' => $paginator->getCollection()->map(fn (Model $model): array => $this->resource($model))->values(), 'links' => ['first' => $paginator->url(1), 'last' => $paginator->url($paginator->lastPage()), 'prev' => $paginator->previousPageUrl(), 'next' => $paginator->nextPageUrl()], 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }

    private function pageSize(Request $request): int
    {
        return min(max((int) $request->input('page.size', $request->integer('per_page', 25)), 1), 100);
    }

    private function resource(Model $model): array
    {
        $attributes = match (true) {
            $model instanceof ProvisionedService => $model->only(['team_id', 'customer_id', 'subscription_id', 'provider', 'external_id', 'state', 'last_error', 'metadata', 'last_reconciled_at', 'created_at', 'updated_at']),
            $model instanceof ProvisioningOperation => $model->only(['team_id', 'provisioned_service_id', 'operation', 'status', 'attempts', 'next_poll_at', 'payload', 'last_error', 'created_at', 'updated_at']),
            default => [],
        };

        return ['id' => (string) $model->getKey(), 'type' => $model instanceof ProvisionedService ? 'provisioned-service' : 'provisioning-operation', 'attributes' => $attributes];
    }
}
