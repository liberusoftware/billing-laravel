<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Orders\Events\QuoteExpired;
use Liberu\Billing\Orders\Models\Quote;

final readonly class ExpireQuotes
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(?int $teamId = null): int
    {
        $quotes = Quote::query()->whereIn('status', ['sent', 'viewed'])->whereNotNull('valid_until')->where('valid_until', '<', now())->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))->get();
        foreach ($quotes as $quote) {
            $quote->update(['status' => 'expired', 'expired_at' => now()]);
            QuoteExpired::dispatch($quote->refresh());
        }

        return $quotes->count();
    }
}
