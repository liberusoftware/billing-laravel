<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Api\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Isp\Actions\CreateAccessService;
use Liberu\Billing\Isp\Actions\CreateIspCapability;
use Liberu\Billing\Isp\Actions\RecordRadiusAccounting;
use Liberu\Billing\Isp\Actions\ResetUsagePeriod;
use Liberu\Billing\Isp\Actions\SynchronizeAccessService;
use Liberu\Billing\Isp\Actions\TransitionAccessService;
use Liberu\Billing\Isp\Actions\TransitionIspCapability;
use Liberu\Billing\Isp\Models\AccessService;
use Liberu\Billing\Isp\Models\IspCapability;
use Liberu\Billing\Isp\Models\RadiusSession;

final class IspCapabilityController extends Controller
{
    public function accounts(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', AccessService::class);

        return $this->paginated(AccessService::query()->forTeam($this->team($request))->latest()->paginate($this->pageSize($request)));
    }

    public function showAccount(Request $request, int $record): JsonResponse
    {
        $service = AccessService::query()->forTeam($this->team($request))->findOrFail($record);
        Gate::authorize('view', $service);

        return response()->json(['data' => $this->resource($service)]);
    }

    public function storeAccessService(Request $request, CreateAccessService $create): JsonResponse
    {
        Gate::authorize('create', AccessService::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:32'], 'monthly_data_limit_bytes' => ['nullable', 'integer', 'min:0'], 'metadata' => ['sometimes', 'array']]);

        return response()->json(['data' => $this->resource($create->handle($this->team($request), $data))], 201);
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', IspCapability::class);

        return $this->paginated(IspCapability::query()->where('team_id', $this->team($request))->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))->latest()->paginate($this->pageSize($request)));
    }

    public function store(Request $request, CreateIspCapability $create): JsonResponse
    {
        Gate::authorize('create', IspCapability::class);
        $data = $request->validate(['type' => ['required', 'in:coverage,installation,radius,usage,equipment,network_adapter'], 'name' => ['required', 'string', 'max:255'], 'identifier' => ['nullable', 'string', 'max:255'], 'configuration' => ['sometimes', 'array']]);

        return response()->json(['data' => $this->resource($create->handle($this->team($request), $data))], 201);
    }

    public function transition(Request $request, int $capability, TransitionIspCapability $transition): JsonResponse
    {
        $instance = IspCapability::query()->whereKey($capability)->where('team_id', $this->team($request))->firstOrFail();
        Gate::authorize('update', $instance);
        $data = $request->validate(['status' => ['required', 'in:pending,active,suspended,cancelled,failed']]);

        return response()->json(['data' => $this->resource($transition->handle($instance, $data['status']))]);
    }

    public function transitionAccessService(Request $request, int $service, TransitionAccessService $transition): JsonResponse
    {
        $instance = AccessService::query()->forTeam($this->team($request))->findOrFail($service);
        Gate::authorize('update', $instance);
        $data = $request->validate(['status' => ['required', 'in:pending,active,suspended,cancelled,failed']]);

        return response()->json(['data' => $this->resource($transition->handle($instance, $data['status']))]);
    }

    public function synchronizeAccessService(Request $request, int $service, SynchronizeAccessService $synchronize): JsonResponse
    {
        $instance = AccessService::query()->forTeam($this->team($request))->findOrFail($service);
        Gate::authorize('update', $instance);
        $data = $request->validate(['adapter' => ['required', 'string', 'max:100']]);

        return response()->json(['data' => $this->resource($synchronize->execute($instance, $data['adapter']))]);
    }

    public function recordAccounting(Request $request, int $service, RecordRadiusAccounting $record): JsonResponse
    {
        $instance = AccessService::query()->forTeam($this->team($request))->findOrFail($service);
        Gate::authorize('update', $instance);
        $data = $request->validate(['accounting_session_id' => ['required', 'string', 'max:255'], 'started_at' => ['required', 'date'], 'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'], 'input_bytes' => ['sometimes', 'integer', 'min:0'], 'output_bytes' => ['sometimes', 'integer', 'min:0'], 'session_seconds' => ['nullable', 'integer', 'min:0'], 'nas_identifier' => ['nullable', 'string', 'max:255'], 'ip_address' => ['nullable', 'ip']]);

        return response()->json(['data' => $this->resource($record->execute($instance, $data))], 201);
    }

    public function resetUsage(Request $request, int $service, ResetUsagePeriod $reset): JsonResponse
    {
        $instance = AccessService::query()->forTeam($this->team($request))->findOrFail($service);
        Gate::authorize('update', $instance);

        return response()->json(['data' => $this->resource($reset->execute($instance))]);
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
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
            $model instanceof AccessService => $model->only(['team_id', 'name', 'status', 'monthly_data_limit_bytes', 'current_period_usage_bytes', 'metadata', 'activated_at', 'suspended_at', 'radius_synced_at', 'created_at', 'updated_at']),
            $model instanceof IspCapability => $model->only(['team_id', 'type', 'name', 'identifier', 'status', 'configuration', 'created_at', 'updated_at']),
            $model instanceof RadiusSession => $model->only(['team_id', 'access_service_id', 'accounting_session_id', 'started_at', 'ended_at', 'input_bytes', 'output_bytes', 'total_bytes', 'session_seconds', 'nas_identifier', 'ip_address', 'created_at', 'updated_at']),
            default => [],
        };

        $type = match (true) {
            $model instanceof AccessService => 'access-service',
            $model instanceof IspCapability => 'isp-capability',
            default => 'radius-session',
        };

        return ['id' => (string) $model->getKey(), 'type' => $type, 'attributes' => $attributes];
    }
}
