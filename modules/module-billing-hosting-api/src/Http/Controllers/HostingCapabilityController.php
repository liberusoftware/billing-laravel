<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Hosting\Actions\CreateHostingAccount;
use Liberu\Billing\Hosting\Actions\CreateHostingCapability;
use Liberu\Billing\Hosting\Actions\PerformHostingOperation;
use Liberu\Billing\Hosting\Actions\TransitionHostingAccount;
use Liberu\Billing\Hosting\Actions\TransitionHostingCapability;
use Liberu\Billing\Hosting\Models\HostingAccount;
use Liberu\Billing\Hosting\Models\HostingCapability;

final class HostingCapabilityController extends Controller
{
    public function storeAccount(Request $request, CreateHostingAccount $create): JsonResponse
    {
        Gate::authorize('create', HostingAccount::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:32'], 'metadata' => ['sometimes', 'array']]);

        return response()->json(['data' => $create->handle($this->team($request), $data)], 201);
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', HostingCapability::class);

        return response()->json(HostingCapability::query()->where('team_id', $this->team($request))->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))->latest()->paginate($request->integer('per_page', 25)));
    }

    public function store(Request $request, CreateHostingCapability $create): JsonResponse
    {
        Gate::authorize('create', HostingCapability::class);
        $data = $request->validate(['type' => ['required', 'in:plan,control_panel,ssl,resource,lifecycle'], 'name' => ['required', 'string', 'max:255'], 'hosting_account_id' => ['nullable', 'integer'], 'provider' => ['nullable', 'string', 'max:100'], 'configuration' => ['sometimes', 'array']]);

        return response()->json(['data' => $create->handle($this->team($request), $data)], 201);
    }

    public function transition(Request $request, HostingCapability $capability, TransitionHostingCapability $transition): JsonResponse
    {
        $capability = HostingCapability::query()->where('team_id', $this->team($request))->findOrFail($capability->getKey());
        Gate::authorize('update', $capability);
        $data = $request->validate(['status' => ['required', 'string']]);

        return response()->json(['data' => $transition->handle($capability, $data['status'])]);
    }

    public function transitionAccount(Request $request, int $account, TransitionHostingAccount $transition): JsonResponse
    {
        $model = HostingAccount::query()->forTeam($this->team($request))->findOrFail($account);
        Gate::authorize('update', $model);
        $data = $request->validate(['status' => ['required', 'string']]);

        return response()->json(['data' => $transition->handle($model, $data['status'])]);
    }

    public function operation(Request $request, int $account, PerformHostingOperation $perform): JsonResponse
    {
        $model = HostingAccount::query()->forTeam($this->team($request))->findOrFail($account);
        Gate::authorize('update', $model);
        $data = $request->validate(['operation' => ['required', 'in:provision,suspend,terminate']]);

        return response()->json(['data' => $perform->handle($model, $data['operation'])]);
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }
}
