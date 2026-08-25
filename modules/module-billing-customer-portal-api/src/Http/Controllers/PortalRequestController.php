<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\CustomerPortal\Actions\CreatePortalRequest;
use Liberu\Billing\CustomerPortal\Actions\TransitionPortalRequest;
use Liberu\Billing\CustomerPortal\Models\PortalRequest;
use Liberu\Billing\CustomerPortal\Queries\ListCustomerPortalRecords;

final class PortalRequestController extends Controller
{
    public function index(Request $request, ListCustomerPortalRecords $list): JsonResponse
    {
        Gate::authorize('viewAny', PortalRequest::class);
        $results = $list->handle($this->team($request), $request->integer('per_page', 25));

        return response()->json(['data' => $results->getCollection()->values(), 'links' => ['next' => $results->nextPageUrl(), 'prev' => $results->previousPageUrl()], 'meta' => ['current_page' => $results->currentPage(), 'last_page' => $results->lastPage(), 'total' => $results->total()]]);
    }

    public function store(Request $request, CreatePortalRequest $create): JsonResponse
    {
        Gate::authorize('create', PortalRequest::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'in:active,closed,failed'], 'metadata' => ['sometimes', 'array']]);

        return response()->json(['data' => $create->handle($this->team($request), $data)], 201);
    }

    public function transition(Request $request, int $record, TransitionPortalRequest $transition): JsonResponse
    {
        $instance = PortalRequest::query()->forTeam($this->team($request))->findOrFail($record);
        Gate::authorize('update', $instance);
        $data = $request->validate(['status' => ['required', 'in:active,closed,failed']]);

        return response()->json(['data' => $transition->handle($instance, $data['status'])]);
    }

    public function show(Request $request, int $record): JsonResponse
    {
        Gate::authorize('viewAny', PortalRequest::class);
        $portalRequest = PortalRequest::query()->forTeam($this->team($request))->findOrFail($record);
        Gate::authorize('view', $portalRequest);

        return response()->json(['data' => $portalRequest]);
    }

    private function team(Request $request): int
    {
        $team = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }
}
