<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Isp\Actions\CreateAccessService;
use Liberu\Billing\Isp\Actions\CreateIspCapability;
use Liberu\Billing\Isp\Actions\TransitionAccessService;
use Liberu\Billing\Isp\Actions\TransitionIspCapability;
use Liberu\Billing\Isp\Models\AccessService;
use Liberu\Billing\Isp\Models\IspCapability;

final class IspCapabilityController extends Controller
{
    public function storeAccessService(Request $request, CreateAccessService $create): JsonResponse
    {
        Gate::authorize('create', AccessService::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:32'], 'metadata' => ['sometimes', 'array']]);

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

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }
}
