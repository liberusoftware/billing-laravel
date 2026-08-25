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
