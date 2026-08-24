<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Invoicing\Actions\AddInvoiceLine;
use Liberu\Billing\Invoicing\Actions\CreateInvoice;
use Liberu\Billing\Invoicing\Actions\FinalizeInvoice;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Queries\ListInvoices;

final class InvoiceController extends Controller
{
    public function index(Request $request, ListInvoices $query): JsonResponse
    {
        Gate::authorize('viewAny', Invoice::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $invoices = $query->execute($teamId === null ? null : (int) $teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $invoices->getCollection()->map(fn (Invoice $invoice): array => $this->resource($invoice))->values(), 'meta' => ['current_page' => $invoices->currentPage(), 'last_page' => $invoices->lastPage()]]);
    }

    public function store(Request $request, CreateInvoice $create): JsonResponse
    {
        Gate::authorize('create', Invoice::class);
        $data = $request->validate(['customer_id' => ['nullable', 'integer', 'min:1'], 'currency' => ['required', 'string', 'size:3', 'alpha'], 'due_at' => ['nullable', 'date'], 'metadata' => ['sometimes', 'array']]);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return response()->json(['data' => $this->resource($create->execute($data))], 201);
    }

    public function line(Request $request, Invoice $invoice, AddInvoiceLine $add): JsonResponse
    {
        Gate::authorize('update', $invoice);
        $data = $request->validate(['description' => ['required', 'string', 'max:1000'], 'quantity' => ['required', 'integer', 'min:1'], 'unit_amount_minor' => ['required', 'integer', 'min:0'], 'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100']]);
        $add->execute($invoice, $data['description'], $data['quantity'], $data['unit_amount_minor'], (float) ($data['tax_rate'] ?? 0));

        return response()->json(['data' => $this->resource($invoice->refresh())]);
    }

    public function finalize(Invoice $invoice, FinalizeInvoice $finalize): JsonResponse
    {
        Gate::authorize('update', $invoice);

        return response()->json(['data' => $this->resource($finalize->execute($invoice))]);
    }

    private function resource(Invoice $invoice): array
    {
        return ['id' => (string) $invoice->getKey(), 'type' => 'billing-invoices', 'attributes' => ['number' => $invoice->number, 'status' => $invoice->status->value, 'currency' => $invoice->currency, 'subtotal_minor' => $invoice->subtotal_minor, 'tax_minor' => $invoice->tax_minor, 'total_minor' => $invoice->total_minor, 'due_at' => $invoice->due_at?->toIso8601String(), 'finalized_at' => $invoice->finalized_at?->toIso8601String()]];
    }
}
