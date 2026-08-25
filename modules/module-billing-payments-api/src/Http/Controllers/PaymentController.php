<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Payments\Actions\AllocatePayment;
use Liberu\Billing\Payments\Actions\CapturePayment;
use Liberu\Billing\Payments\Actions\CreatePayment;
use Liberu\Billing\Payments\Actions\OpenDispute;
use Liberu\Billing\Payments\Actions\ReconcilePayment;
use Liberu\Billing\Payments\Actions\RefundPayment;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Queries\ListPayments;

final class PaymentController extends Controller
{
    public function index(Request $request, ListPayments $query): JsonResponse
    {
        Gate::authorize('viewAny', Payment::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $payments = $query->execute($teamId === null ? null : (int) $teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $payments->getCollection()->map(fn (Payment $payment): array => $this->resource($payment))->values(), 'meta' => ['current_page' => $payments->currentPage(), 'last_page' => $payments->lastPage()]]);
    }

    public function store(Request $request, CreatePayment $create): JsonResponse
    {
        Gate::authorize('create', Payment::class);
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'gateway' => ['nullable', 'string', 'max:100'],
            'metadata' => ['sometimes', 'array'],
        ]);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return response()->json(['data' => $this->resource($create->execute($data))], 201);
    }

    public function capture(Payment $payment, CapturePayment $capture): JsonResponse
    {
        Gate::authorize('update', $payment);

        return response()->json(['data' => $this->resource($capture->execute($payment))]);
    }

    public function refund(Request $request, Payment $payment, RefundPayment $refund): JsonResponse
    {
        Gate::authorize('update', $payment);
        $data = $request->validate(['amount_minor' => ['required', 'integer', 'min:1'], 'reason' => ['sometimes', 'string', 'max:255']]);
        $refund->execute($payment, (int) $data['amount_minor'], (string) ($data['reason'] ?? 'requested'));

        return response()->json(['data' => $this->resource($payment->refresh())]);
    }

    public function dispute(Request $request, Payment $payment, OpenDispute $dispute): JsonResponse
    {
        Gate::authorize('update', $payment);
        $data = $request->validate(['amount_minor' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:255']]);
        $dispute->execute($payment, (int) $data['amount_minor'], $data['reason']);

        return response()->json(['data' => $this->resource($payment->refresh())]);
    }

    public function allocate(Request $request, Payment $payment, AllocatePayment $allocate): JsonResponse
    {
        Gate::authorize('update', $payment);
        $data = $request->validate(['amount_minor' => ['required', 'integer', 'min:1'], 'invoice_id' => ['nullable', 'integer', 'min:1']]);
        $allocation = $allocate->execute($payment, (int) $data['amount_minor'], isset($data['invoice_id']) ? (int) $data['invoice_id'] : null);

        return response()->json(['data' => $allocation], 201);
    }

    public function reconcile(Request $request, Payment $payment, ReconcilePayment $reconcile): JsonResponse
    {
        Gate::authorize('update', $payment);
        $data = $request->validate(['provider_reference' => ['required', 'string', 'max:255'], 'matched' => ['sometimes', 'boolean'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $record = $reconcile->execute($payment, $data['provider_reference'], (bool) ($data['matched'] ?? true), $data['notes'] ?? null);

        return response()->json(['data' => $record], 201);
    }

    private function resource(Payment $payment): array
    {
        return ['id' => (string) $payment->getKey(), 'type' => 'billing-payments', 'attributes' => [
            'amount_minor' => $payment->amount_minor,
            'currency' => $payment->currency,
            'status' => $payment->status->value,
            'payment_method' => $payment->payment_method,
            'gateway' => $payment->gateway,
            'provider_reference' => $payment->provider_reference,
            'captured_at' => $payment->captured_at?->toIso8601String(),
            'refunded_minor' => $payment->refunded_minor,
        ]];
    }
}
