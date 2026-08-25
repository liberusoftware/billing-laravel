<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\CustomerPortal\Models\PortalRequest;

final class TransitionPortalRequest
{
    public function handle(PortalRequest $request, string $status): PortalRequest
    {
        if (! in_array($status, ['active', 'closed', 'failed'], true)) {
            throw new InvalidArgumentException('Portal request status is invalid.');
        }

        return DB::transaction(function () use ($request, $status): PortalRequest {
            $request->update(['status' => $status]);

            return $request->refresh();
        });
    }
}
