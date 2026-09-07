<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceManagementController extends Controller
{
    public function __construct(protected BillingService $billingService, protected HostingService $hostingService, protected DomainService $domainService) {}

    public function index(): Factory|View
    {
        $subscriptions = auth()->user()->customer?->subscriptions()->with('productService')->latest()->get() ?? collect();

        return view(
            'client.services.index',
            compact('subscriptions')
        );
    }

    public function show(Subscription $subscription): Factory|View
    {
        $this->ensureOwnership($subscription);

        $availableUpgrades = Products_Service::where(
            'type',
            $subscription->productService->type
        )
            ->where(
                'price',
                '>',
                $subscription->productService->price
            )
            ->get();

        $availableDowngrades = Products_Service::where(
            'type',
            $subscription->productService->type
        )
            ->where(
                'price',
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
        abort_unless($newService->type === $subscription->productService->type, 422);

        // Calculate prorated amount
        $proratedAmount = $this->calculateProration(
            $subscription,
            $newService
        );

        // Generate a pending invoice; provider changes are applied only after payment.
        $invoice = $this->billingService->generateInvoice($subscription);
        $invoice->total_amount = $proratedAmount;
        $invoice->save();
        SubscriptionChange::query()->create([
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->customer_id,
            'invoice_id' => $invoice->id,
            'target_product_service_id' => $newService->id,
            'type' => 'upgrade',
            'status' => 'pending',
        ]);

        return redirect()->route(
            'client.services.show',
            $subscription
        )
            ->with(
                'success',
                'Service upgraded successfully'
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
        abort_unless($newService->type === $subscription->productService->type, 422);

        // Schedule downgrade for end of billing period
        $subscription->scheduled_change = [
            'type' => 'downgrade',
            'product_service_id' => $newService->id,
            'effective_date' => $subscription->end_date,
        ];
        $subscription->save();

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

        // Schedule cancellation for end of billing period
        $subscription->scheduled_change = [
            'type' => 'cancel',
            'effective_date' => $subscription->end_date,
        ];
        $subscription->save();

        return redirect()->route('client.services.index')
            ->with(
                'success',
                'Service scheduled for cancellation at end of billing period'
            );
    }

    private function calculateProration(Subscription $subscription, $newService): float
    {
        $daysRemaining = now()->diffInDays($subscription->end_date);
        $totalDays = $subscription->start_date->diffInDays($subscription->end_date);

        $oldAmount = $subscription->productService->price;
        $newAmount = $newService->price;

        $proratedRefund = ((float) $oldAmount / $totalDays) * $daysRemaining;
        $proratedCharge = ((float) $newAmount / $totalDays) * $daysRemaining;

        return $proratedCharge - $proratedRefund;
    }

    private function ensureOwnership(Subscription $subscription): void
    {
        $customer = auth()->user()?->customer;

        if ($customer === null || $subscription->customer_id !== $customer->id) {
            throw new NotFoundHttpException();
        }
    }
}
