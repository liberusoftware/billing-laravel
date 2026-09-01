<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Api\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
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

        return $this->paginated(InvoiceSupport::query()->where('team_id', $this->team($request))->latest()->paginate($this->pageSize($request)));
    }

    public function store(Request $request, CreateInvoiceSupport $create): JsonResponse
    {
        Gate::authorize('create', InvoiceSupport::class);
        $data = $request->validate(['invoice_id' => ['required', 'integer', 'min:1'], 'type' => ['required', 'in:tax,credit,adjustment,pdf,delivery'], 'amount_minor' => ['sometimes', 'integer', 'min:0'], 'currency' => ['nullable', 'string', 'size:3', 'alpha'], 'destination' => ['nullable', 'string', 'max:255'], 'payload' => ['sometimes', 'array']]);

        return response()->json(['data' => $this->resource($create->execute($this->team($request), $data))], 201);
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }

    private function paginated(LengthAwarePaginator $results): JsonResponse
    {
        return response()->json(['data' => $results->getCollection()->map(fn (Model $model): array => $this->resource($model))->values(), 'links' => ['next' => $results->nextPageUrl(), 'prev' => $results->previousPageUrl()], 'meta' => ['current_page' => $results->currentPage(), 'last_page' => $results->lastPage(), 'per_page' => $results->perPage(), 'total' => $results->total()]]);
    }

    private function pageSize(Request $request): int
    {
        return min(max((int) $request->input('page.size', $request->integer('per_page', 25)), 1), 100);
    }

    private function resource(InvoiceSupport $support): array
    {
        return ['id' => (string) $support->getKey(), 'type' => 'invoice-support', 'attributes' => $support->only(['team_id', 'invoice_id', 'type', 'amount_minor', 'currency', 'destination', 'payload', 'status', 'created_at', 'updated_at'])];
    }
}
