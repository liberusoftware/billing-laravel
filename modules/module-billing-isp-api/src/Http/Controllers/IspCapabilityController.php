<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Isp\Actions\CreateIspCapability;
use Liberu\Billing\Isp\Models\IspCapability;

final class IspCapabilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', IspCapability::class);

        return response()->json(IspCapability::query()->where('team_id', $this->team($request))->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))->latest()->paginate($request->integer('per_page', 25)));
    }

    public function store(Request $request, CreateIspCapability $create): JsonResponse
    {
        Gate::authorize('create', IspCapability::class);
        $data = $request->validate(['type' => ['required', 'in:coverage,installation,radius,usage,equipment,network_adapter'], 'name' => ['required', 'string', 'max:255'], 'identifier' => ['nullable', 'string', 'max:255'], 'configuration' => ['sometimes', 'array']]);

        return response()->json(['data' => $create->handle($this->team($request), $data)], 201);
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }
}
