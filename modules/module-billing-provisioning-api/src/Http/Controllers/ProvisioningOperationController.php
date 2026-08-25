<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Api\Http\Controllers;

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
    public function storeService(Request $request, CreateProvisionedService $create): JsonResponse
    {
        Gate::authorize('create', ProvisionedService::class);
        $data = $request->validate(['customer_id' => ['nullable', 'integer', 'min:1'], 'subscription_id' => ['nullable', 'integer', 'min:1'], 'provider' => ['required', 'string', 'max:100'], 'external_id' => ['nullable', 'string', 'max:255'], 'metadata' => ['sometimes', 'array']]);
        $data['team_id'] = $this->team($request);

        return response()->json(['data' => $create->execute($data)], 201);
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ProvisioningOperation::class);

        return response()->json(ProvisioningOperation::query()->where('team_id', $this->team($request))->latest()->paginate($request->integer('per_page', 25)));
    }

    public function queue(Request $request, ProvisionedService $provisionedService, QueueProvisioningOperation $queue): JsonResponse
    {
        Gate::authorize('update', $provisionedService);
        $data = $request->validate(['operation' => ['required', 'in:provision,deprovision,poll,reconcile,rollback'], 'payload' => ['sometimes', 'array']]);

        return response()->json(['data' => $queue->execute($provisionedService, $data['operation'], $data['payload'] ?? [])], 202);
    }

    public function reconcile(ProvisionedService $provisionedService, ReconcileProvisionedService $reconcile): JsonResponse
    {
        Gate::authorize('update', $provisionedService);

        return response()->json(['data' => $reconcile->execute($provisionedService)]);
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }
}
