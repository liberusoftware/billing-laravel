<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Communications\Actions\ImportCommunicationUsage;
use Liberu\Billing\Communications\Actions\ProvisionCommunicationNumber;
use Liberu\Billing\Communications\Models\CommunicationNumber;
use Liberu\Billing\Communications\Models\CommunicationProvider;
use Liberu\Billing\Communications\Models\CommunicationUsageImport;

final class CommunicationsController extends Controller
{
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
