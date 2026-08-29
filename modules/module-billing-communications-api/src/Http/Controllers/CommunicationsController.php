<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Communications\Actions\CreateCommunicationProvider;
use Liberu\Billing\Communications\Actions\CreateCommunicationService;
use Liberu\Billing\Communications\Actions\CreateVoipAccount;
use Liberu\Billing\Communications\Actions\ImportCommunicationUsage;
use Liberu\Billing\Communications\Actions\IngestCallDetailRecord;
use Liberu\Billing\Communications\Actions\ProvisionCommunicationNumber;
use Liberu\Billing\Communications\Actions\ProvisionVoipAccount;
use Liberu\Billing\Communications\Actions\TransitionCommunicationNumber;
use Liberu\Billing\Communications\Actions\TransitionCommunicationService;
use Liberu\Billing\Communications\Models\CallDetailRecord;
use Liberu\Billing\Communications\Models\CallRateRule;
use Liberu\Billing\Communications\Models\CommunicationNumber;
use Liberu\Billing\Communications\Models\CommunicationProvider;
use Liberu\Billing\Communications\Models\CommunicationService;
use Liberu\Billing\Communications\Models\CommunicationUsageImport;
use Liberu\Billing\Communications\Models\VoipAccount;

final class CommunicationsController extends Controller
{
    public function createService(Request $request, CreateCommunicationService $create): JsonResponse
    {
        Gate::authorize('create', CommunicationService::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:32'], 'metadata' => ['sometimes', 'array']]);

        return response()->json(['data' => $create->handle($this->team($request), $data)], 201);
    }

    public function numbers(Request $request): JsonResponse
    {
        return $this->list($request, CommunicationNumber::class);
    }

    public function providers(Request $request): JsonResponse
    {
        return $this->list($request, CommunicationProvider::class);
    }

    public function usageImports(Request $request): JsonResponse
    {
        return $this->list($request, CommunicationUsageImport::class);
    }

    public function provisionNumber(Request $request, ProvisionCommunicationNumber $provision): JsonResponse
    {
        Gate::authorize('create', CommunicationNumber::class);
        $data = $request->validate(['number' => ['required', 'string', 'max:64'], 'type' => ['sometimes', 'string', 'max:32'], 'service_id' => ['nullable', 'integer']]);

        return response()->json(['data' => $provision->handle($this->team($request), $data)], 201);
    }

    public function importUsage(Request $request, ImportCommunicationUsage $import): JsonResponse
    {
        Gate::authorize('create', CommunicationUsageImport::class);
        $data = $request->validate(['provider' => ['required', 'string', 'max:100'], 'rows' => ['required', 'integer', 'min:1'], 'total_amount_minor' => ['sometimes', 'integer', 'min:0'], 'currency' => ['sometimes', 'string', 'size:3', 'alpha']]);

        return response()->json(['data' => $import->handle($this->team($request), $data)], 201);
    }

    public function transitionNumber(Request $request, int $number, TransitionCommunicationNumber $transition): JsonResponse
    {
        $instance = CommunicationNumber::query()->whereKey($number)->where('team_id', $this->team($request))->firstOrFail();
        Gate::authorize('update', $instance);
        $data = $request->validate(['status' => ['required', 'in:available,active,suspended,released,failed']]);

        return response()->json(['data' => $transition->handle($instance, $data['status'])]);
    }

    public function transitionService(Request $request, int $service, TransitionCommunicationService $transition): JsonResponse
    {
        $instance = CommunicationService::query()->forTeam($this->team($request))->findOrFail($service);
        Gate::authorize('update', $instance);
        $data = $request->validate(['status' => ['required', 'in:pending,active,suspended,cancelled,failed']]);

        return response()->json(['data' => $transition->handle($instance, $data['status'])]);
    }

    public function createProvider(Request $request, CreateCommunicationProvider $create): JsonResponse
    {
        Gate::authorize('create', CommunicationProvider::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'driver' => ['required', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'max:32'],
            'configuration' => ['sometimes', 'array'],
        ]);

        return response()->json(['data' => $create->handle($this->team($request), $data)], 201);
    }

    public function voiceAccounts(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', VoipAccount::class);

        return response()->json(VoipAccount::query()->forTeam($this->team($request))->latest()->paginate($request->integer('per_page', 25)));
    }

    public function createVoiceAccount(Request $request, CreateVoipAccount $create): JsonResponse
    {
        Gate::authorize('create', VoipAccount::class);
        $data = $request->validate(['customer_id' => ['required', 'integer', 'min:1'], 'subscription_id' => ['nullable', 'integer', 'min:1'], 'platform' => ['required', 'string', 'max:100'], 'sip_username' => ['required', 'string', 'max:255'], 'sip_secret' => ['required', 'string', 'max:1000'], 'caller_id' => ['nullable', 'string', 'max:255'], 'credit_limit' => ['nullable', 'numeric', 'min:0'], 'max_concurrent_calls' => ['sometimes', 'integer', 'min:1'], 'international_enabled' => ['sometimes', 'boolean']]);

        return response()->json(['data' => $create->handle($this->team($request), $data)], 201);
    }

    public function provisionVoiceAccount(Request $request, int $account, ProvisionVoipAccount $provision): JsonResponse
    {
        $model = VoipAccount::query()->forTeam($this->team($request))->findOrFail($account);
        Gate::authorize('update', $model);

        return response()->json(['data' => $provision->handle($model)], 202);
    }

    public function rates(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', CallRateRule::class);

        return response()->json(CallRateRule::query()->forTeam($this->team($request))->latest()->paginate($request->integer('per_page', 25)));
    }

    public function createRate(Request $request): JsonResponse
    {
        Gate::authorize('create', CallRateRule::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'destination_prefix' => ['required', 'string', 'max:64'], 'connection_fee' => ['sometimes', 'numeric', 'min:0'], 'rate_per_minute' => ['required', 'numeric', 'min:0'], 'billing_increment_seconds' => ['sometimes', 'integer', 'min:1'], 'currency' => ['sometimes', 'string', 'size:3', 'alpha'], 'is_active' => ['sometimes', 'boolean']]);

        return response()->json(['data' => CallRateRule::query()->create([...$data, 'team_id' => $this->team($request), 'currency' => strtoupper((string) ($data['currency'] ?? 'USD'))])], 201);
    }

    public function callRecords(Request $request, int $account): JsonResponse
    {
        $model = VoipAccount::query()->forTeam($this->team($request))->findOrFail($account);
        Gate::authorize('view', $model);

        return response()->json(CallDetailRecord::query()->forTeam($this->team($request))->where('voip_account_id', $model->getKey())->latest('started_at')->paginate($request->integer('per_page', 25)));
    }

    public function ingestCallRecord(Request $request, int $account, IngestCallDetailRecord $ingest): JsonResponse
    {
        $model = VoipAccount::query()->forTeam($this->team($request))->findOrFail($account);
        Gate::authorize('update', $model);
        $data = $request->validate(['external_id' => ['required', 'string', 'max:255'], 'source' => ['required', 'string', 'max:255'], 'destination' => ['required', 'string', 'max:255'], 'direction' => ['sometimes', 'string', 'max:32'], 'started_at' => ['required', 'date'], 'answered_at' => ['nullable', 'date'], 'ended_at' => ['nullable', 'date'], 'duration_seconds' => ['sometimes', 'integer', 'min:0'], 'disposition' => ['sometimes', 'string', 'max:64'], 'metadata' => ['sometimes', 'array']]);

        return response()->json(['data' => $ingest->handle($model, $data)], 202);
    }

    private function list(Request $request, string $model): JsonResponse
    {
        Gate::authorize('viewAny', $model);

        return response()->json($model::query()->where('team_id', $this->team($request))->latest()->paginate($request->integer('per_page', 25)));
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }
}
