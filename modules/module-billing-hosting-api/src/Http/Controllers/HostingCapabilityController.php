<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Api\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
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
    public function accounts(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', HostingAccount::class);

        return $this->paginated(HostingAccount::query()->forTeam($this->team($request))->latest()->paginate($this->pageSize($request)));
    }

    public function showAccount(Request $request, int $record): JsonResponse
    {
        $account = HostingAccount::query()->forTeam($this->team($request))->findOrFail($record);
        Gate::authorize('view', $account);

        return response()->json(['data' => $this->resource($account)]);
    }

    public function storeAccount(Request $request, CreateHostingAccount $create): JsonResponse
    {
        Gate::authorize('create', HostingAccount::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:32'], 'metadata' => ['sometimes', 'array']]);

        return response()->json(['data' => $this->resource($create->handle($this->team($request), $data))], 201);
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', HostingCapability::class);

        return $this->paginated(HostingCapability::query()->where('team_id', $this->team($request))->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))->latest()->paginate($this->pageSize($request)));
    }

    public function store(Request $request, CreateHostingCapability $create): JsonResponse
    {
        Gate::authorize('create', HostingCapability::class);
        $data = $request->validate(['type' => ['required', 'in:plan,control_panel,ssl,resource,lifecycle'], 'name' => ['required', 'string', 'max:255'], 'hosting_account_id' => ['nullable', 'integer'], 'provider' => ['nullable', 'string', 'max:100'], 'configuration' => ['sometimes', 'array']]);

        return response()->json(['data' => $this->resource($create->handle($this->team($request), $data))], 201);
    }

    public function transition(Request $request, HostingCapability $capability, TransitionHostingCapability $transition): JsonResponse
    {
        $capability = HostingCapability::query()->where('team_id', $this->team($request))->findOrFail($capability->getKey());
        Gate::authorize('update', $capability);
        $data = $request->validate(['status' => ['required', 'string']]);

        return response()->json(['data' => $this->resource($transition->handle($capability, $data['status']))]);
    }

    public function transitionAccount(Request $request, int $account, TransitionHostingAccount $transition): JsonResponse
    {
        $model = HostingAccount::query()->forTeam($this->team($request))->findOrFail($account);
        Gate::authorize('update', $model);
        $data = $request->validate(['status' => ['required', 'string']]);

        return response()->json(['data' => $this->resource($transition->handle($model, $data['status']))]);
    }

    public function operation(Request $request, int $account, PerformHostingOperation $perform): JsonResponse
    {
        $model = HostingAccount::query()->forTeam($this->team($request))->findOrFail($account);
        Gate::authorize('update', $model);
        $data = $request->validate(['operation' => ['required', 'in:provision,suspend,unsuspend,change_package,terminate,add_addon,remove_addon'], 'account' => ['sometimes', 'array'], 'server' => ['sometimes', 'array']]);

        if (isset($data['account']) || isset($data['server'])) {
            $model->update(['metadata' => array_merge((array) $model->metadata, array_filter(['account' => $data['account'] ?? null, 'server' => $data['server'] ?? null]))]);
        }

        return response()->json(['data' => $this->resource($perform->handle($model, $data['operation']))]);
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
            $model instanceof HostingAccount => $model->only(['name', 'status', 'team_id', 'metadata', 'created_at', 'updated_at']),
            $model instanceof HostingCapability => $model->only(['type', 'name', 'status', 'hosting_account_id', 'provider', 'configuration', 'created_at', 'updated_at']),
            default => [],
        };

        return ['id' => (string) $model->getKey(), 'type' => $model instanceof HostingAccount ? 'hosting-account' : 'hosting-capability', 'attributes' => $attributes];
    }
}
