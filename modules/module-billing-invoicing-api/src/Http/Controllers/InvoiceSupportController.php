<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Invoicing\Actions\CreateInvoiceSupport;
use Liberu\Billing\Invoicing\Models\InvoiceSupport;

final class InvoiceSupportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', InvoiceSupport::class);

        return response()->json(InvoiceSupport::query()->where('team_id', $this->team($request))->latest()->paginate($request->integer('per_page', 25)));
    }

    public function store(Request $request, CreateInvoiceSupport $create): JsonResponse
    {
        Gate::authorize('create', InvoiceSupport::class);
        $data = $request->validate(['invoice_id' => ['required', 'integer', 'min:1'], 'type' => ['required', 'in:tax,credit,adjustment,pdf,delivery'], 'amount_minor' => ['sometimes', 'integer', 'min:0'], 'currency' => ['nullable', 'string', 'size:3', 'alpha'], 'destination' => ['nullable', 'string', 'max:255'], 'payload' => ['sometimes', 'array']]);

        return response()->json(['data' => $create->execute($this->team($request), $data)], 201);
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }
}
