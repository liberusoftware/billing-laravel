<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Api\Http\Controllers;

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

        return response()->json($list->handle($this->team($request), $request->string('type')->toString() ?: null, $request->integer('per_page', 25)));
    }

    public function store(Request $request, CreatePortalItem $create): JsonResponse
    {
        Gate::authorize('create', PortalItem::class);
        $data = $request->validate(['type' => ['required', 'in:profile,orders,services,usage,invoices,payments,tickets,changes,cancellation'], 'subject' => ['required', 'string', 'max:255'], 'customer_id' => ['nullable', 'integer'], 'payload' => ['sometimes', 'array']]);

        return response()->json(['data' => $create->handle($this->team($request), $data)], 201);
    }

    public function transition(Request $request, int $item, TransitionPortalItem $transition): JsonResponse
    {
        $instance = PortalItem::query()->whereKey($item)->where('team_id', $this->team($request))->firstOrFail();
        Gate::authorize('update', $instance);
        $data = $request->validate(['status' => ['required', 'in:open,in_progress,completed,cancelled,failed']]);

        return response()->json(['data' => $transition->handle($instance, $data['status'])]);
    }

    private function team(Request $request): int
    {
        $team = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }
}
