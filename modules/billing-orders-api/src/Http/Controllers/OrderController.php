<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Orders\Actions\CreateOrder;
use Liberu\Billing\Orders\Models\Order;
use Liberu\Billing\Orders\Queries\ListOrders;

final class OrderController extends Controller
{
    public function index(Request $request, ListOrders $query): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $orders = $query->execute($teamId === null ? null : (int) $teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $orders->getCollection()->map(fn (Order $o): array => $this->resource($o))->values(), 'meta' => ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage()]]);
    }

    public function store(Request $request, CreateOrder $create): JsonResponse
    {
        Gate::authorize('create', Order::class);
        $data = $request->validate(['currency' => ['required', 'string', 'size:3', 'alpha'], 'subtotal_minor' => ['required', 'integer', 'min:0'], 'discount_minor' => ['sometimes', 'integer', 'min:0'], 'tax_minor' => ['sometimes', 'integer', 'min:0'], 'customer_id' => ['nullable', 'integer'], 'quote_id' => ['nullable', 'integer'], 'fraud_review_required' => ['sometimes', 'boolean'], 'agreement' => ['nullable', 'array'], 'metadata' => ['sometimes', 'array']]);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return response()->json(['data' => $this->resource($create->execute($data))], 201);
    }

    private function resource(Order $o): array
    {
        return ['id' => (string) $o->getKey(), 'type' => 'billing-orders', 'attributes' => ['order_number' => $o->order_number, 'currency' => $o->currency, 'subtotal_minor' => $o->subtotal_minor, 'discount_minor' => $o->discount_minor, 'tax_minor' => $o->tax_minor, 'total_minor' => $o->total_minor, 'status' => $o->status->value, 'fraud_status' => $o->fraud_status->value, 'agreement' => $o->agreement, 'change_orders' => $o->change_orders ?? []]];
    }
}
