<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\CustomerPortal\Actions\TransitionPortalItem;
use Liberu\Billing\CustomerPortal\Actions\TransitionPortalRequest;
use Liberu\Billing\CustomerPortal\Models\PortalItem;
use Liberu\Billing\CustomerPortal\Models\PortalRequest;

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
