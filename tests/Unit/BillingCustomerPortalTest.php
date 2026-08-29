<?php

use App\Models\Customer;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Liberu\Billing\CustomerPortal\Actions\CreatePortalItem;
use Liberu\Billing\CustomerPortal\Actions\TransitionPortalItem;
use Liberu\Billing\CustomerPortal\Actions\TransitionPortalRequest;
use Liberu\Billing\CustomerPortal\Models\PortalItem;
use Liberu\Billing\CustomerPortal\Models\PortalRequest;
use Liberu\Billing\CustomerPortal\Queries\GetCustomerBillingSummary;

uses(RefreshDatabase::class);

it('transitions portal items and requests through supported states', function () {
    $item = PortalItem::query()->create(['team_id' => 10, 'type' => 'cancellation', 'status' => 'open', 'subject' => 'Cancel service']);
    $request = PortalRequest::query()->create(['team_id' => 10, 'name' => 'Customer request', 'status' => 'active']);

    expect(app(TransitionPortalItem::class)->handle($item, 'cancelled')->status)->toBe('cancelled')
        ->and(app(TransitionPortalRequest::class)->handle($request, 'closed')->status)->toBe('closed');
});

it('rejects unsupported portal lifecycle states', function () {
    $item = PortalItem::query()->create(['team_id' => 10, 'type' => 'orders', 'status' => 'open', 'subject' => 'Order change']);

    expect(fn () => app(TransitionPortalItem::class)->handle($item, 'unknown'))
        ->toThrow(InvalidArgumentException::class);
});

it('does not reopen a portal request after its persisted state becomes closed', function (): void {
    $request = PortalRequest::query()->create(['team_id' => 10, 'name' => 'Stale request', 'status' => 'active']);
    $request->refresh();
    PortalRequest::query()->whereKey($request->getKey())->update(['status' => 'closed']);

    expect(fn () => app(TransitionPortalRequest::class)->handle($request, 'active'))
        ->toThrow(InvalidArgumentException::class, 'Closed portal requests cannot be reopened.');
});

it('does not reopen a portal item after its persisted state becomes completed', function (): void {
    $item = PortalItem::query()->create(['team_id' => 10, 'type' => 'services', 'status' => 'open', 'subject' => 'Provision service']);
    $item->refresh();
    PortalItem::query()->whereKey($item->getKey())->update(['status' => 'completed']);

    expect(fn () => app(TransitionPortalItem::class)->handle($item, 'in_progress'))
        ->toThrow(InvalidArgumentException::class, 'Completed or cancelled portal items cannot be reopened.');
});

it('rejects a portal item customer owned by another team', function (): void {
    $team = Team::factory()->create(['id' => 20]);
    $customerId = Customer::factory()->create(['team_id' => $team->getKey()])->getKey();

    expect(fn () => app(CreatePortalItem::class)->handle(10, [
        'customer_id' => $customerId, 'type' => 'invoices', 'subject' => 'View invoices',
    ]))->toThrow(InvalidArgumentException::class, 'Customer reference is invalid.');
});

it('returns customer billing history, status, and payment trends', function (): void {
    $team = Team::factory()->create();
    $customer = Customer::factory()->create(['team_id' => $team->getKey()]);
    $createdAt = CarbonImmutable::parse('2026-08-01 10:00:00');
    $invoice = DB::table('billing_invoices')->insertGetId([
        'team_id' => $team->getKey(), 'customer_id' => $customer->getKey(), 'number' => 'INV-PORTAL-1', 'status' => 'finalized',
        'currency' => 'USD', 'subtotal_minor' => 1000, 'tax_minor' => 0, 'total_minor' => 1000,
        'due_at' => $createdAt->subDay(), 'finalized_at' => $createdAt, 'created_at' => $createdAt, 'updated_at' => $createdAt,
    ]);
    $payment = DB::table('billing_payments')->insertGetId([
        'team_id' => $team->getKey(), 'customer_id' => $customer->getKey(), 'amount_minor' => 400, 'currency' => 'USD',
        'status' => 'captured', 'captured_at' => $createdAt, 'refunded_minor' => 0, 'created_at' => $createdAt, 'updated_at' => $createdAt,
    ]);
    DB::table('billing_payment_allocations')->insert([
        'payment_id' => $payment, 'invoice_id' => $invoice, 'amount_minor' => 400, 'currency' => 'USD', 'created_at' => $createdAt, 'updated_at' => $createdAt,
    ]);

    $summary = app(GetCustomerBillingSummary::class)->handle($team->getKey(), $customer->getKey());

    expect($summary['history'][0]['balance_minor'])->toBe(600)
        ->and($summary['status'])->toBe(['total_invoiced' => 1000, 'total_paid' => 400, 'total_outstanding' => 1000, 'overdue_amount' => 1000])
        ->and($summary['trends'][0])->toBe(['month' => '2026-08', 'total_paid' => 400]);
});
