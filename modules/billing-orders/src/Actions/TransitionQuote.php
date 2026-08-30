<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Orders\Events\QuoteStatusChanged;
use Liberu\Billing\Orders\Models\Quote;

final readonly class TransitionQuote
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Quote $quote, string $status): Quote
    {
        if (! in_array($status, ['draft', 'sent', 'viewed', 'accepted', 'declined', 'expired'], true)) {
            throw new \InvalidArgumentException('Quote status is invalid.');
        }

        return $this->database->transaction(function () use ($quote, $status): Quote {
            $locked = Quote::query()->lockForUpdate()->findOrFail($quote->getKey());
            if (in_array($locked->status, ['accepted', 'declined', 'expired'], true) && $locked->status !== $status) {
                throw new \LogicException('A terminal quote cannot transition.');
            }
            $timestamps = match ($status) {
                'sent' => ['sent_at' => now()], 'viewed' => ['viewed_at' => now()], 'accepted' => ['accepted_at' => now()], 'declined' => ['declined_at' => now()], 'expired' => ['expired_at' => now()], default => [],
            };
            $from = (string) $locked->status;
            $locked->update(['status' => $status, ...$timestamps]);
            QuoteStatusChanged::dispatch($locked, $from, $status);

            return $locked->refresh();
        });
    }
}
