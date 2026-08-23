<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ServiceSuspension;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class ServiceAutomationService
{
    public function __construct(
        protected WebhookService $webhookService
    ) {}

    /**
     * Auto-suspend services for overdue invoices
     */
    public function suspendOverdueServices(int $daysOverdue = 7): int
    {
        $suspended = 0;
        $overdueDate = now()->subDays($daysOverdue);

        $overdueInvoices = Invoice::whereIn(
            'status',
            [
                'pending',
                'overdue',
            ]
        )
            ->where(
                'due_date',
                '<=',
                $overdueDate
            )
            ->whereDoesntHave('subscription.activeSuspension')
            ->with('subscription')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            if ($invoice->subscription) {
                $this->suspendService(
                    $invoice->subscription,
                    $invoice,
                    'overdue_payment'
                );
                $suspended++;
            }
        }

        return $suspended;
    }

    /**
     * Suspend a service
     */
    public function suspendService(
        Subscription $subscription,
        ?Invoice $invoice = null,
        string $reason = 'manual',
        ?string $notes = null,
        ?int $userId = null
    ): ServiceSuspension {
        $suspension = ServiceSuspension::create(
            [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice?->id,
                'reason' => $reason,
                'notes' => $notes,
                'suspended_at' => now(),
                'suspended_by' => $userId,
            ]
        );

        // Update subscription status
        $subscription->update(['status' => 'suspended']);

        // Trigger webhook
        $this->webhookService->dispatch(
            WebhookService::EVENT_SERVICE_SUSPENDED,
            [
                'subscription_id' => $subscription->id,
                'suspension_id' => $suspension->id,
                'reason' => $reason,
                'suspended_at' => $suspension->suspended_at->toIso8601String(),
            ],
            $subscription->team_id
        );

        Log::info(
            'Service suspended',
            [
                'subscription_id' => $subscription->id,
                'reason' => $reason,
            ]
        );

        return $suspension;
    }

    /**
     * Unsuspend a service
     */
    public function unsuspendService(Subscription $subscription, ?int $userId = null): bool
    {
        $activeSuspension = $subscription->activeSuspension;

        if (! $activeSuspension) {
            return false;
        }

        $activeSuspension->unsuspend($userId);

        // Update subscription status
        $subscription->update(['status' => 'active']);

        // Trigger webhook
        $this->webhookService->dispatch(
            WebhookService::EVENT_SERVICE_PROVISIONED,
            [
                'subscription_id' => $subscription->id,
                'unsuspended_at' => now()->toIso8601String(),
            ],
            $subscription->team_id
        );

        Log::info(
            'Service unsuspended',
            [
                'subscription_id' => $subscription->id,
            ]
        );

        return true;
    }

    /**
     * Auto-unsuspend services when invoices are paid
     */
    public function autoUnsuspendOnPayment(Invoice $invoice): bool
    {
        if ($invoice->status !== 'paid' || ! $invoice->subscription) {
            return false;
        }

        $activeSuspension = $invoice->subscription->activeSuspension;

        if ($activeSuspension && $activeSuspension->reason === 'overdue_payment') {
            return $this->unsuspendService($invoice->subscription);
        }

        return false;
    }

    /**
     * Terminate services
     */
    public function terminateService(Subscription $subscription, ?int $userId = null): bool
    {
        $subscription->update(
            [
                'status' => 'terminated',
                'ends_at' => now(),
            ]
        );

        // Trigger webhook
        $this->webhookService->dispatch(
            WebhookService::EVENT_SERVICE_TERMINATED,
            [
                'subscription_id' => $subscription->id,
                'terminated_at' => now()->toIso8601String(),
            ],
            $subscription->team_id
        );

        Log::info(
            'Service terminated',
            [
                'subscription_id' => $subscription->id,
            ]
        );

        return true;
    }
}
