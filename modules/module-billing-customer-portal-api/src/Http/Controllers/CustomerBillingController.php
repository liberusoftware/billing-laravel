<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Api\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\CustomerPortal\Models\PortalItem;
use Liberu\Billing\CustomerPortal\Queries\GetCustomerBillingSummary;

final class CustomerBillingController extends Controller
{
    public function show(Request $request, int $customer, GetCustomerBillingSummary $summary): JsonResponse
    {
        Gate::authorize('viewAny', PortalItem::class);
        $data = $request->validate(['start_at' => ['nullable', 'date'], 'end_at' => ['nullable', 'date', 'after_or_equal:start_at']]);

        return response()->json(['data' => $summary->handle($this->team($request), $customer, isset($data['start_at']) ? CarbonImmutable::parse($data['start_at']) : null, isset($data['end_at']) ? CarbonImmutable::parse($data['end_at']) : null)]);
    }

    private function team(Request $request): int
    {
        $team = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }
}
