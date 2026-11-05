<?php

namespace App\Http\Controllers\Client;

use App\Enums\BillingCycle;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Products_Service;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use App\Services\BillingService;
use App\Services\DomainService;
use App\Services\HostingService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceManagementController extends Controller
{
    public function __construct(protected BillingService $billingService, protected HostingService $hostingService, protected DomainService $domainService) {}

    public function index(): Factory|View
    {
        $subscriptions = auth()->user()->customer?->subscriptions()->with(['productService', 'subscriptionPlan'])->latest()->get() ?? collect();

        return view(
            'client.services.index',
            compact('subscriptions')
        );
    }

    public function show(Subscription $subscription): Factory|View
    {
        $this->ensureOwnership($subscription);

        abort_if($subscription->productService === null, 404);

        $availableUpgrades = Products_Service::where('team_id', $subscription->productService->team_id)
            ->where('hosting_server_id', $subscription->productService->hosting_server_id)->where(
                'type',
                $subscription->productService->type
            )
            ->where(
                'base_price',
                '>',
                $subscription->productService->price
            )
            ->get();

        $availableDowngrades = Products_Service::where('team_id', $subscription->productService->team_id)
            ->where('hosting_server_id', $subscription->productService->hosting_server_id)->where(
                'type',
                $subscription->productService->type
            )
            ->where(
                'base_price',
                '<',
                $subscription->productService->price
            )
            ->get();

        return view(
            'client.services.show',
            compact(
                'subscription',
                'availableUpgrades',
                'availableDowngrades'
            )
        );
    }

    public function upgrade(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->ensureOwnership($subscription);
        $request->validate(
            [
                'new_service_id' => 'required|exists:products_services,id',
            ]
        );

        $newService = Products_Service::findOrFail($request->new_service_id);

        $this->ensureChangeAllowed($subscription, $newService, 'upgrade');

        // Calculate prorated amount
        $proratedAmount = $this->calculateProration(
            $subscription,
            $newService
        );

        DB::transaction(function () use ($subscription, $newService, $proratedAmount): void {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            $this->ensureChangeAllowed($subscription, $newService, 'upgrade');

            $invoice = Invoice::query()->create([
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
                'issue_date' => now(),
                'due_date' => $subscription->end_date,
                'currency' => $subscription->currency,
                'total_amount' => $proratedAmount,
                'status' => 'pending',
            ]);
            $invoice->forceFill(['team_id' => $subscription->team_id])->save();
            $invoice->items()->create([
                'description' => 'Prorated upgrade to '.$newService->name,
                'quantity' => 1,
                'unit_price' => $proratedAmount,
                'total_price' => $proratedAmount,
                'currency' => $subscription->currency,
                'product_service_id' => $newService->id,
            ]);
            SubscriptionChange::query()->create([
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
                'invoice_id' => $invoice->id,
                'target_product_service_id' => $newService->id,
                'type' => 'upgrade',
                'status' => 'pending',
            ]);
        });

        return redirect()->route(
            'client.services.show',
            $subscription
        )
            ->with(
                'success',
                'Upgrade requested. Pay the invoice to apply your new service.'
            );
    }

    public function downgrade(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->ensureOwnership($subscription);
        $request->validate(
            [
                'new_service_id' => 'required|exists:products_services,id',
            ]
        );

        $newService = Products_Service::findOrFail($request->new_service_id);

        $this->ensureChangeAllowed($subscription, $newService, 'downgrade');

        DB::transaction(function () use ($subscription, $newService): void {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            $this->ensureChangeAllowed($subscription, $newService, 'downgrade');
            $subscription->update(['scheduled_change' => [
                'type' => 'downgrade',
                'product_service_id' => $newService->id,
                'effective_date' => $subscription->end_date,
            ]]);
        });

        return redirect()->route(
            'client.services.show',
            $subscription
        )
            ->with(
                'success',
                'Service scheduled for downgrade at end of billing period'
            );
    }

    public function cancel(Subscription $subscription): RedirectResponse
    {
        $this->ensureOwnership($subscription);

        abort_unless($subscription->status === 'active' && $subscription->end_date?->isFuture(), 422);

        DB::transaction(function () use ($subscription): void {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            abort_unless($subscription->scheduled_change === null, 422);
            abort_if(SubscriptionChange::query()->where('subscription_id', $subscription->id)->whereIn('status', ['pending', 'queued', 'processing'])->exists(), 422);
            $subscription->update(['scheduled_change' => [
                'type' => 'cancel',
                'effective_date' => $subscription->end_date,
            ]]);
        });

        return redirect()->route('client.services.index')
            ->with(
                'success',
                'Service scheduled for cancellation at end of billing period'
            );
    }

    private function ensureChangeAllowed(Subscription $subscription, Products_Service $newService, string $direction): void
    {
        abort_unless($subscription->status === 'active' && $subscription->end_date?->isFuture(), 422);
        abort_unless($subscription->productService !== null, 422);
        abort_unless($newService->type === $subscription->productService->type, 422);
        abort_unless($newService->team_id === $subscription->productService->team_id, 422);
        abort_unless($newService->hosting_server_id === $subscription->productService->hosting_server_id, 422);
        abort_unless($subscription->scheduled_change === null, 422);
        abort_if(SubscriptionChange::query()->where('subscription_id', $subscription->id)->whereIn('status', ['pending', 'queued', 'processing'])->exists(), 422);

        $difference = (float) $newService->price - (float) $subscription->productService->price;
        abort_unless($direction === 'upgrade' ? $difference > 0 : $difference < 0, 422);
    }

    private function calculateProration(Subscription $subscription, Products_Service $newService): float
    {
        $totalSeconds = $subscription->start_date->diffInSeconds($subscription->end_date, false);
        abort_unless($totalSeconds > 0, 422);
        $remainingSeconds = max(0, min($totalSeconds, now()->diffInSeconds($subscription->end_date, false)));

        return round(((float) $newService->price - (float) $subscription->productService->price)
            * (BillingCycle::tryFrom($subscription->renewal_period)?->priceMultiplier() ?? 1)
            * $remainingSeconds / $totalSeconds, 2);
    }

    private function ensureOwnership(Subscription $subscription): void
    {
        $customer = auth()->user()?->customer;

        if ($customer === null || $subscription->customer_id !== $customer->id) {
            throw new NotFoundHttpException();
        }
    }
}
