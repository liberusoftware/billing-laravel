<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Core\Actions\CreateBillingAccount;
use Liberu\Billing\Core\Actions\DeleteBillingAccount;
use Liberu\Billing\Core\Actions\TransitionBillingAccount;
use Liberu\Billing\Core\Actions\UpdateBillingAccount;
use Liberu\Billing\Core\Enums\BillingAccountStatus;
use Liberu\Billing\Core\Models\BillingAccount;
use Liberu\Billing\Core\Queries\ListBillingAccounts;

final class BillingAccountController extends Controller
{
    public function index(Request $request, ListBillingAccounts $query): JsonResponse
    {
        Gate::authorize('viewAny', BillingAccount::class);

        $teamId = data_get($request->user(), 'current_team_id')
            ?? data_get($request->user(), 'currentTeam.id');

        $accounts = $query->execute($teamId !== null ? (int) $teamId : null, (int) $request->integer('per_page', 25));

        return response()->json([
            'data' => $accounts->getCollection()->map(fn (BillingAccount $account): array => $this->resource($account))->values(),
            'links' => ['next' => $accounts->nextPageUrl(), 'prev' => $accounts->previousPageUrl()],
            'meta' => ['current_page' => $accounts->currentPage(), 'last_page' => $accounts->lastPage()],
        ]);
    }

    public function store(Request $request, CreateBillingAccount $create): JsonResponse
    {
        Gate::authorize('create', BillingAccount::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'settings' => ['sometimes', 'array'],
        ]);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $data['team_id'] = $teamId !== null ? (int) $teamId : null;

        $account = $create->execute($data);

        return response()->json(['data' => $this->resource($account)], 201);
    }

    public function update(Request $request, int $account, UpdateBillingAccount $update): JsonResponse
    {
        $instance = $this->forCurrentTeam($request, $account);
        Gate::authorize('update', $instance);
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:255'], 'currency' => ['sometimes', 'string', 'size:3', 'alpha'], 'settings' => ['sometimes', 'array']]);

        return response()->json(['data' => $this->resource($update->execute($instance, $data))]);
    }

    public function transition(Request $request, int $account, TransitionBillingAccount $transition): JsonResponse
    {
        $instance = $this->forCurrentTeam($request, $account);
        Gate::authorize('update', $instance);
        $data = $request->validate(['status' => ['required', 'in:active,suspended,closed']]);

        return response()->json(['data' => $this->resource($transition->execute($instance, BillingAccountStatus::from($data['status'])))]);
    }

    public function destroy(Request $request, int $account, DeleteBillingAccount $delete): JsonResponse
    {
        $instance = $this->forCurrentTeam($request, $account);
        Gate::authorize('delete', $instance);
        $delete->execute($instance);

        return response()->json(status: 204);
    }

    private function resource(BillingAccount $account): array
    {
        return [
            'id' => (string) $account->getKey(),
            'type' => 'billing-core-account',
            'attributes' => [
                'name' => $account->name,
                'currency' => $account->currency,
                'status' => $account->status->value,
                'settings' => $account->settings ?? [],
                'created_at' => $account->created_at?->toISOString(),
            ],
        ];
    }

    private function forCurrentTeam(Request $request, int $account): BillingAccount
    {
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');

        return BillingAccount::query()->whereKey($account)->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $teamId))->firstOrFail();
    }
}
