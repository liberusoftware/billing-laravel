<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Api\Http\Controllers;

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

final class IspCapabilityController extends Controller
{
    public function storeAccessService(Request $request, CreateAccessService $create): JsonResponse
    {
        Gate::authorize('create', AccessService::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:32'], 'monthly_data_limit_bytes' => ['nullable', 'integer', 'min:0'], 'metadata' => ['sometimes', 'array']]);

        return response()->json(['data' => $create->handle($this->team($request), $data)], 201);
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', IspCapability::class);

        return response()->json(IspCapability::query()->where('team_id', $this->team($request))->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))->latest()->paginate($request->integer('per_page', 25)));
    }

    public function store(Request $request, CreateIspCapability $create): JsonResponse
    {
        Gate::authorize('create', IspCapability::class);
        $data = $request->validate(['type' => ['required', 'in:coverage,installation,radius,usage,equipment,network_adapter'], 'name' => ['required', 'string', 'max:255'], 'identifier' => ['nullable', 'string', 'max:255'], 'configuration' => ['sometimes', 'array']]);

        return response()->json(['data' => $create->handle($this->team($request), $data)], 201);
    }

    public function transition(Request $request, int $capability, TransitionIspCapability $transition): JsonResponse
    {
        $instance = IspCapability::query()->whereKey($capability)->where('team_id', $this->team($request))->firstOrFail();
        Gate::authorize('update', $instance);
        $data = $request->validate(['status' => ['required', 'in:pending,active,suspended,cancelled,failed']]);

        return response()->json(['data' => $transition->handle($instance, $data['status'])]);
    }

    public function transitionAccessService(Request $request, int $service, TransitionAccessService $transition): JsonResponse
    {
        $instance = AccessService::query()->forTeam($this->team($request))->findOrFail($service);
        Gate::authorize('update', $instance);
        $data = $request->validate(['status' => ['required', 'in:pending,active,suspended,cancelled,failed']]);

        return response()->json(['data' => $transition->handle($instance, $data['status'])]);
    }

    public function synchronizeAccessService(Request $request, int $service, SynchronizeAccessService $synchronize): JsonResponse
    {
        $instance = AccessService::query()->forTeam($this->team($request))->findOrFail($service);
        Gate::authorize('update', $instance);
        $data = $request->validate(['adapter' => ['required', 'string', 'max:100']]);

        return response()->json(['data' => $synchronize->execute($instance, $data['adapter'])]);
    }

    public function recordAccounting(Request $request, int $service, RecordRadiusAccounting $record): JsonResponse
    {
        $instance = AccessService::query()->forTeam($this->team($request))->findOrFail($service);
        Gate::authorize('update', $instance);
        $data = $request->validate(['accounting_session_id' => ['required', 'string', 'max:255'], 'started_at' => ['required', 'date'], 'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'], 'input_bytes' => ['sometimes', 'integer', 'min:0'], 'output_bytes' => ['sometimes', 'integer', 'min:0'], 'session_seconds' => ['nullable', 'integer', 'min:0'], 'nas_identifier' => ['nullable', 'string', 'max:255'], 'ip_address' => ['nullable', 'ip']]);

        return response()->json(['data' => $record->execute($instance, $data)], 201);
    }

    public function resetUsage(Request $request, int $service, ResetUsagePeriod $reset): JsonResponse
    {
        $instance = AccessService::query()->forTeam($this->team($request))->findOrFail($service);
        Gate::authorize('update', $instance);

        return response()->json(['data' => $reset->execute($instance)]);
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }
}
