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
            $locked = PortalRequest::query()->lockForUpdate()->findOrFail($request->getKey());
            if ($locked->status === 'closed' && $status !== 'closed') {
                throw new InvalidArgumentException('Closed portal requests cannot be reopened.');
            }

            $locked->update(['status' => $status]);

            return $locked->refresh();
        });
    }
}
