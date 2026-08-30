<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Billing\Orders\Events\QuoteCreated;
use Liberu\Billing\Orders\Models\Quote;
use Liberu\Billing\Orders\Support\CustomerReference;

final class CreateQuote
{
    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): Quote
    {
        $total = (int) ($attributes['total_minor'] ?? 0);
        if ($total < 0 || empty($attributes['items'])) {
            throw new \InvalidArgumentException('Quote total and items are invalid.');
        }

        $teamId = $attributes['team_id'] ?? null;
        $customerId = CustomerReference::assertBelongsToTeam(app('db'), $attributes['customer_id'] ?? null, $teamId);

        return DB::transaction(function () use ($teamId, $customerId, $attributes, $total): Quote {
            $quote = Quote::query()->create(['team_id' => $teamId, 'customer_id' => $customerId, 'quote_number' => $attributes['quote_number'] ?? 'QUO-'.strtoupper(bin2hex(random_bytes(5))), 'currency' => strtoupper($attributes['currency']), 'total_minor' => $total, 'items' => $attributes['items'], 'valid_until' => $attributes['valid_until'] ?? null, 'status' => 'draft']);
            QuoteCreated::dispatch($quote);

            return $quote;
        });
    }
}
