<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Payments\Actions\CreatePaymentMandate;
use Liberu\Billing\Payments\Actions\CreatePaymentMethod;
use Liberu\Billing\Payments\Actions\TransitionPaymentMandate;
use Liberu\Billing\Payments\Actions\TransitionPaymentMethod;
use Liberu\Billing\Payments\Enums\PaymentMethodStatus;
use Liberu\Billing\Payments\Models\PaymentMandate;
use Liberu\Billing\Payments\Models\PaymentMethod;

final class PaymentMethodController extends Controller
{
    public function methods(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PaymentMethod::class);
        $teamId = $this->teamId($request);
        $methods = PaymentMethod::query()->where('team_id', $teamId)->latest()->paginate($this->pageSize($request));

        return response()->json([
            'data' => collect($methods->items())->map(fn (PaymentMethod $method): array => $this->methodResource($method))->values(),
            'meta' => ['current_page' => $methods->currentPage(), 'last_page' => $methods->lastPage(), 'per_page' => $methods->perPage(), 'total' => $methods->total()],
        ]);
    }

    public function storeMethod(Request $request, CreatePaymentMethod $create): JsonResponse
    {
        Gate::authorize('create', PaymentMethod::class);
        $data = $request->validate(['customer_id' => ['nullable', 'integer', 'min:1'], 'type' => ['required', 'string', 'max:50'], 'provider' => ['required', 'string', 'max:50'], 'provider_reference' => ['nullable', 'string', 'max:255'], 'display_name' => ['nullable', 'string', 'max:255'], 'last_four' => ['nullable', 'digits:4'], 'expires_at' => ['nullable', 'date'], 'is_default' => ['sometimes', 'boolean'], 'metadata' => ['sometimes', 'array']]);
        $data['team_id'] = $this->teamId($request);

        return response()->json(['data' => $this->methodResource($create->execute($data))], 201);
    }

    public function mandates(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PaymentMandate::class);
        $mandates = PaymentMandate::query()->where('team_id', $this->teamId($request))->latest()->paginate($this->pageSize($request));

        return response()->json([
            'data' => collect($mandates->items())->map(fn (PaymentMandate $mandate): array => $this->mandateResource($mandate))->values(),
            'meta' => ['current_page' => $mandates->currentPage(), 'last_page' => $mandates->lastPage(), 'per_page' => $mandates->perPage(), 'total' => $mandates->total()],
        ]);
    }

    public function storeMandate(Request $request, CreatePaymentMandate $create): JsonResponse
    {
        Gate::authorize('create', PaymentMandate::class);
        $data = $request->validate(['customer_id' => ['nullable', 'integer', 'min:1'], 'payment_method_id' => ['required', 'integer', 'min:1'], 'provider' => ['required', 'string', 'max:50'], 'provider_reference' => ['nullable', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:50'], 'metadata' => ['sometimes', 'array']]);
        $data['team_id'] = $this->teamId($request);

        return response()->json(['data' => $this->mandateResource($create->execute($data))], 201);
    }

    public function transitionMethod(Request $request, PaymentMethod $method, TransitionPaymentMethod $transition): JsonResponse
    {
        $method = PaymentMethod::query()->whereKey($method->getKey())->where('team_id', $this->teamId($request))->firstOrFail();
        Gate::authorize('update', $method);
        $data = $request->validate(['status' => ['required', 'in:active,inactive']]);

        return response()->json(['data' => $this->methodResource($transition->execute($method, PaymentMethodStatus::from($data['status'])))]);
    }

    public function transitionMandate(Request $request, PaymentMandate $mandate, TransitionPaymentMandate $transition): JsonResponse
    {
        $mandate = PaymentMandate::query()->whereKey($mandate->getKey())->where('team_id', $this->teamId($request))->firstOrFail();
        Gate::authorize('update', $mandate);
        $data = $request->validate(['status' => ['required', 'in:pending,active,revoked,expired']]);

        return response()->json(['data' => $this->mandateResource($transition->execute($mandate, $data['status']))]);
    }

    private function teamId(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }

    private function pageSize(Request $request): int
    {
        return min(max((int) $request->input('page.size', $request->integer('per_page', 25)), 1), 100);
    }

    private function methodResource(PaymentMethod $method): array
    {
        return [
            'id' => (string) $method->getKey(),
            'type' => 'billing-payment-methods',
            'attributes' => [
                'customer_id' => $method->customer_id,
                'type' => $method->type,
                'provider' => $method->provider,
                'display_name' => $method->display_name,
                'last_four' => $method->last_four,
                'expires_at' => $method->expires_at?->toDateString(),
                'is_default' => $method->is_default,
                'status' => $method->status?->value,
            ],
        ];
    }

    private function mandateResource(PaymentMandate $mandate): array
    {
        return [
            'id' => (string) $mandate->getKey(),
            'type' => 'billing-payment-mandates',
            'attributes' => [
                'customer_id' => $mandate->customer_id,
                'payment_method_id' => $mandate->payment_method_id,
                'provider' => $mandate->provider,
                'status' => $mandate->status,
            ],
        ];
    }
}
