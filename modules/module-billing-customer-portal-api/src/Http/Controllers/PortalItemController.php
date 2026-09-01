<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Api\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\CustomerPortal\Actions\CreatePortalItem;
use Liberu\Billing\CustomerPortal\Actions\TransitionPortalItem;
use Liberu\Billing\CustomerPortal\Models\PortalItem;
use Liberu\Billing\CustomerPortal\Queries\ListPortalItems;

final class PortalItemController extends Controller
{
    public function index(Request $request, ListPortalItems $list): JsonResponse
    {
        Gate::authorize('viewAny', PortalItem::class);

        return $this->paginated($list->handle($this->team($request), $request->string('type')->toString() ?: null, $this->pageSize($request)));
    }

    public function store(Request $request, CreatePortalItem $create): JsonResponse
    {
        Gate::authorize('create', PortalItem::class);
        $data = $request->validate(['type' => ['required', 'in:profile,orders,services,usage,invoices,payments,tickets,changes,cancellation'], 'subject' => ['required', 'string', 'max:255'], 'customer_id' => ['nullable', 'integer'], 'payload' => ['sometimes', 'array']]);

        return response()->json(['data' => $this->resource($create->handle($this->team($request), $data))], 201);
    }

    public function transition(Request $request, int $item, TransitionPortalItem $transition): JsonResponse
    {
        $instance = PortalItem::query()->whereKey($item)->where('team_id', $this->team($request))->firstOrFail();
        Gate::authorize('update', $instance);
        $data = $request->validate(['status' => ['required', 'in:open,in_progress,completed,cancelled,failed']]);

        return response()->json(['data' => $this->resource($transition->handle($instance, $data['status']))]);
    }

    private function team(Request $request): int
    {
        $team = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }

    private function paginated(LengthAwarePaginator $results): JsonResponse
    {
        return response()->json(['data' => $results->getCollection()->map(fn (Model $model): array => $this->resource($model))->values(), 'links' => ['next' => $results->nextPageUrl(), 'prev' => $results->previousPageUrl()], 'meta' => ['current_page' => $results->currentPage(), 'last_page' => $results->lastPage(), 'per_page' => $results->perPage(), 'total' => $results->total()]]);
    }

    private function pageSize(Request $request): int
    {
        return min(max((int) $request->input('page.size', $request->integer('per_page', 25)), 1), 100);
    }

    private function resource(PortalItem $item): array
    {
        return ['id' => (string) $item->getKey(), 'type' => 'customer-portal-item', 'attributes' => $item->only(['team_id', 'customer_id', 'type', 'subject', 'status', 'payload', 'created_at', 'updated_at'])];
    }
}
