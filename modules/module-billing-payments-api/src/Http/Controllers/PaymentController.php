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
use Liberu\Billing\Payments\Models\PaymentAllocation;
use Liberu\Billing\Payments\Models\PaymentReconciliation;
use Liberu\Billing\Payments\Queries\ListPayments;

final class PaymentController extends Controller
{
    public function index(Request $request, ListPayments $query): JsonResponse
    {
        Gate::authorize('viewAny', Payment::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $payments = $query->execute($teamId === null ? null : (int) $teamId, $this->pageSize($request));

        return response()->json(['data' => $payments->getCollection()->map(fn (Payment $payment): array => $this->resource($payment))->values(), 'meta' => ['current_page' => $payments->currentPage(), 'last_page' => $payments->lastPage()]]);
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        $payment = $this->forCurrentTeam($request, $payment);
        Gate::authorize('view', $payment);

        return response()->json(['data' => $this->resource($payment)]);
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

    public function capture(Request $request, Payment $payment, CapturePayment $capture): JsonResponse
    {
        $payment = $this->forCurrentTeam($request, $payment);
        Gate::authorize('update', $payment);

        return response()->json(['data' => $this->resource($capture->execute($payment))]);
    }

    public function refund(Request $request, Payment $payment, RefundPayment $refund): JsonResponse
    {
        $payment = $this->forCurrentTeam($request, $payment);
        Gate::authorize('update', $payment);
        $data = $request->validate(['amount_minor' => ['required', 'integer', 'min:1'], 'reason' => ['sometimes', 'string', 'max:255']]);
        $refund->execute($payment, (int) $data['amount_minor'], (string) ($data['reason'] ?? 'requested'));

        return response()->json(['data' => $this->resource($payment->refresh())]);
    }

    public function dispute(Request $request, Payment $payment, OpenDispute $dispute): JsonResponse
    {
        $payment = $this->forCurrentTeam($request, $payment);
        Gate::authorize('update', $payment);
        $data = $request->validate(['amount_minor' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:255']]);
        $dispute->execute($payment, (int) $data['amount_minor'], $data['reason']);

        return response()->json(['data' => $this->resource($payment->refresh())]);
    }

    public function allocate(Request $request, Payment $payment, AllocatePayment $allocate): JsonResponse
    {
        $payment = $this->forCurrentTeam($request, $payment);
        Gate::authorize('update', $payment);
        $data = $request->validate(['amount_minor' => ['required', 'integer', 'min:1'], 'invoice_id' => ['nullable', 'integer', 'min:1']]);
        $allocation = $allocate->execute($payment, (int) $data['amount_minor'], isset($data['invoice_id']) ? (int) $data['invoice_id'] : null);

        return response()->json(['data' => $this->allocationResource($allocation)], 201);
    }

    public function reconcile(Request $request, Payment $payment, ReconcilePayment $reconcile): JsonResponse
    {
        $payment = $this->forCurrentTeam($request, $payment);
        Gate::authorize('update', $payment);
        $data = $request->validate(['provider_reference' => ['required', 'string', 'max:255'], 'matched' => ['sometimes', 'boolean'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $record = $reconcile->execute($payment, $data['provider_reference'], (bool) ($data['matched'] ?? true), $data['notes'] ?? null);

        return response()->json(['data' => $this->reconciliationResource($record)], 201);
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

    private function allocationResource(PaymentAllocation $allocation): array
    {
        return ['id' => (string) $allocation->getKey(), 'type' => 'billing-payment-allocations', 'attributes' => [
            'payment_id' => $allocation->payment_id,
            'invoice_id' => $allocation->invoice_id,
            'amount_minor' => $allocation->amount_minor,
            'currency' => $allocation->currency,
        ]];
    }

    private function reconciliationResource(PaymentReconciliation $reconciliation): array
    {
        return ['id' => (string) $reconciliation->getKey(), 'type' => 'billing-payment-reconciliations', 'attributes' => [
            'payment_id' => $reconciliation->payment_id,
            'status' => $reconciliation->status->value,
            'provider_reference' => $reconciliation->provider_reference,
            'notes' => $reconciliation->notes,
        ]];
    }

    private function forCurrentTeam(Request $request, Payment $payment): Payment
    {
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');

        return Payment::query()->whereKey($payment->getKey())->where('team_id', $teamId)->firstOrFail();
    }

    private function pageSize(Request $request): int
    {
        $requested = $request->input('page.size', $request->integer('per_page', 25));

        return min(max((int) $requested, 1), 100);
    }
}
