<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\InvoiceResource;
use App\Models\Invoice;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(
            Invoice::class,
            'invoice'
        );
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $invoices = Invoice::query()
            ->where('team_id', $this->currentTeamId($request))
            ->when(
                $request->status,
                fn ($q) => $q->where(
                    'status',
                    $request->status
                )
            )
            ->when(
                $request->customer_id,
                fn ($q) => $q->where(
                    'customer_id',
                    $request->customer_id
                )
            )
            ->when(
                $request->from_date,
                fn ($q) => $q->where(
                    'issue_date',
                    '>=',
                    $request->from_date
                )
            )
            ->when(
                $request->to_date,
                fn ($q) => $q->where(
                    'issue_date',
                    '<=',
                    $request->to_date
                )
            )
            ->paginate($request->per_page ?? 15);

        return InvoiceResource::collection($invoices);
    }

    public function show(Request $request, Invoice $invoice): InvoiceResource
    {
        $this->assertSameTeam($request, $invoice);

        return new InvoiceResource(
            $invoice->load(
                [
                    'customer',
                    'items',
                ]
            )
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                'customer_id' => ['required', Rule::exists('customers', 'id')->where('team_id', $this->currentTeamId($request))],
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.price' => 'required|numeric|min:0',
            ]
        );

        $itemRows = array_map(
            static fn (array $item): array => [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'total_price' => $item['quantity'] * $item['price'],
                'currency' => $validated['currency'] ?? 'USD',
            ],
            $validated['items']
        );

        $totalAmount = array_sum(
            array_column(
                $itemRows,
                'total_price'
            )
        );

        $invoiceData = array_merge(
            collect($validated)->except('items')->toArray(),
            ['total_amount' => $totalAmount]
        );
        $invoice = new Invoice($invoiceData);
        $invoice->setAttribute(
            'status',
            'pending'
        );
        $invoice->team_id = $this->currentTeamId($request);
        $invoice->save();
        $invoice->refresh();

        $invoice->items()->createMany($itemRows);

        return new InvoiceResource(
            $invoice->load(
                [
                    'customer',
                    'items',
                ]
            )
        )->response()->setStatusCode(201);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->assertSameTeam($request, $invoice);

        if ($invoice->status === 'paid') {
            return response()->json(
                [
                    'message' => 'Cannot update a paid invoice',
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $validated = $request->validate(
            [
                'issue_date' => 'sometimes|date',
                'due_date' => 'sometimes|date|after_or_equal:issue_date',
                'status' => 'sometimes|in:draft,sent,paid,cancelled',
            ]
        );

        $invoice->update($validated);

        return new InvoiceResource(
            $invoice->load(
                [
                    'customer',
                    'items',
                ]
            )
        );
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        $this->assertSameTeam($request, $invoice);

        if ($invoice->status !== 'draft') {
            return response()->json(
                [
                    'message' => 'Only draft invoices can be deleted',
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $invoice->delete();

        return response()->noContent();
    }

    public function download(Request $request, Invoice $invoice)
    {
        $this->assertSameTeam($request, $invoice);

        $pdf = PDF::loadView(
            'invoices.pdf',
            [
                'invoice' => $invoice->load(
                    [
                        'customer',
                        'items',
                    ]
                ),
            ]
        );

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    /** Block cross-tenant access: another team's invoice is treated as not found. */
    private function assertSameTeam(Request $request, Invoice $invoice): void
    {
        abort_unless($invoice->team_id === $this->currentTeamId($request), 404);
    }

    private function currentTeamId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user?->current_team_id;
    }
}
