<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\BusinessConnector;
use App\Enums\ConnectorType;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DomainEventMessage;
use App\Models\ExternalConnection;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\DomainEventBus;
use App\Services\IntegrationSyncService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class CrossPlatformIntegrationController extends Controller
{
    public function __construct(
        private readonly IntegrationSyncService $sync,
        private readonly BusinessConnector $connector,
        private readonly DomainEventBus $events,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->connections($request)->withCount('syncRecords')->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        $data = $request->validate([
            'connector_type' => ['required', Rule::enum(ConnectorType::class)],
            'provider' => 'required|string|max:100',
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('external_connections', 'name')
                    ->where('team_id', $teamId)
                    ->where('connector_type', $request->input('connector_type')),
            ],
            'base_url' => 'required|url:https|max:2048',
            'access_token' => 'required|string|min:8|max:4096',
            'signing_secret' => 'nullable|string|min:16|max:4096',
            'resource_mappings' => 'nullable|array',
            'resource_mappings.*' => 'string|starts_with:/|max:255',
            'event_subscriptions' => 'nullable|array',
            'event_subscriptions.*' => 'string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);
        $data['team_id'] = $teamId;

        return response()->json(ExternalConnection::query()->create($data), Response::HTTP_CREATED);
    }

    public function update(Request $request, int $connection): JsonResponse
    {
        $model = $this->connection($request, $connection);
        $model->update($request->validate([
            'provider' => 'sometimes|string|max:100',
            'name' => 'sometimes|string|max:255',
            'base_url' => 'sometimes|url:https|max:2048',
            'access_token' => 'sometimes|string|min:8|max:4096',
            'signing_secret' => 'nullable|string|min:16|max:4096',
            'resource_mappings' => 'nullable|array',
            'resource_mappings.*' => 'string|starts_with:/|max:255',
            'event_subscriptions' => 'nullable|array',
            'event_subscriptions.*' => 'string|max:255',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json($model->refresh());
    }

    public function destroy(Request $request, int $connection): Response
    {
        $this->connection($request, $connection)->delete();

        return response()->noContent();
    }

    public function pushResource(Request $request, string $resource, int $id): JsonResponse
    {
        $teamId = $this->teamId($request);
        $count = match ($resource) {
            'customers' => $this->sync->synchronizeCustomer(
                Customer::query()->where('team_id', $teamId)->findOrFail($id)
            ),
            'invoices' => $this->sync->synchronizeInvoice(
                Invoice::query()->where('team_id', $teamId)->findOrFail($id)
            ),
            'payments' => $this->sync->synchronizePayment(
                Payment::query()->where('team_id', $teamId)->findOrFail($id)
            ),
            'tax-rates' => $this->sync->synchronizeTaxRate(
                TaxRate::query()->where('team_id', $teamId)->findOrFail($id)
            ),
            default => throw new InvalidArgumentException('Unsupported integration resource.'),
        };

        return response()->json(['connections_synchronized' => $count]);
    }

    public function pullResource(Request $request, int $connection, string $resource): JsonResponse
    {
        abort_unless(in_array($resource, [
            'customers', 'contacts', 'leads', 'opportunities',
            'invoices', 'payments', 'tax-rates',
        ], true), Response::HTTP_NOT_FOUND);
        $model = $this->connection($request, $connection);
        $result = $this->connector->pull($model, $resource, $request->string('cursor')->toString() ?: null);
        $model->update(['last_synced_at' => now()]);

        return response()->json($result);
    }

    public function convertLead(Request $request, int $lead): JsonResponse
    {
        $model = Lead::query()->where('team_id', $this->teamId($request))->findOrFail($lead);

        return response()->json($this->sync->convertLead($model), Response::HTTP_CREATED);
    }

    public function billOpportunity(Request $request, int $customer): JsonResponse
    {
        $model = Customer::query()->where('team_id', $this->teamId($request))->findOrFail($customer);
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'name' => 'nullable|string|max:255',
        ]);

        return response()->json($this->sync->billOpportunity($model, $data), Response::HTTP_CREATED);
    }

    public function eventLog(Request $request): JsonResponse
    {
        return response()->json(
            DomainEventMessage::query()->where('team_id', $this->teamId($request))
                ->with('deliveries')->latest('occurred_at')->paginate(100)
        );
    }

    public function replayEvent(Request $request, string $event): JsonResponse
    {
        $model = DomainEventMessage::query()
            ->where('team_id', $this->teamId($request))->findOrFail($event);

        return response()->json($this->events->replay($model), Response::HTTP_ACCEPTED);
    }

    /** @return Builder<ExternalConnection> */
    private function connections(Request $request): Builder
    {
        return ExternalConnection::query()->where('team_id', $this->teamId($request));
    }

    private function connection(Request $request, int $id): ExternalConnection
    {
        return $this->connections($request)->findOrFail($id);
    }

    private function teamId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user?->current_team_id;
    }
}
