<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Payments\Actions\CreatePaymentMandate;
use Liberu\Billing\Payments\Actions\CreatePaymentMethod;
use Liberu\Billing\Payments\Models\PaymentMandate;
use Liberu\Billing\Payments\Models\PaymentMethod;

final class PaymentMethodController extends Controller
{
    public function methods(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PaymentMethod::class);
        $teamId = $this->teamId($request);
        $methods = PaymentMethod::query()->where('team_id', $teamId)->latest()->paginate($request->integer('per_page', 25));

        return response()->json(['data' => $methods->items(), 'meta' => ['current_page' => $methods->currentPage(), 'last_page' => $methods->lastPage()]]);
    }

    public function storeMethod(Request $request, CreatePaymentMethod $create): JsonResponse
    {
        Gate::authorize('create', PaymentMethod::class);
        $data = $request->validate(['customer_id' => ['nullable', 'integer', 'min:1'], 'type' => ['required', 'string', 'max:50'], 'provider' => ['required', 'string', 'max:50'], 'provider_reference' => ['nullable', 'string', 'max:255'], 'display_name' => ['nullable', 'string', 'max:255'], 'last_four' => ['nullable', 'digits:4'], 'expires_at' => ['nullable', 'date'], 'is_default' => ['sometimes', 'boolean'], 'metadata' => ['sometimes', 'array']]);
        $data['team_id'] = $this->teamId($request);

        return response()->json(['data' => $create->execute($data)], 201);
    }

    public function mandates(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PaymentMandate::class);
        $mandates = PaymentMandate::query()->where('team_id', $this->teamId($request))->latest()->paginate($request->integer('per_page', 25));

        return response()->json(['data' => $mandates->items(), 'meta' => ['current_page' => $mandates->currentPage(), 'last_page' => $mandates->lastPage()]]);
    }

    public function storeMandate(Request $request, CreatePaymentMandate $create): JsonResponse
    {
        Gate::authorize('create', PaymentMandate::class);
        $data = $request->validate(['customer_id' => ['nullable', 'integer', 'min:1'], 'payment_method_id' => ['required', 'integer', 'min:1'], 'provider' => ['required', 'string', 'max:50'], 'provider_reference' => ['nullable', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:50'], 'metadata' => ['sometimes', 'array']]);
        $data['team_id'] = $this->teamId($request);

        return response()->json(['data' => $create->execute($data)], 201);
    }

    private function teamId(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }
}
