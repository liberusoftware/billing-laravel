<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Billing\Orders\Models\Quote;

final class CreateQuote
{
    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): Quote
    {
        $total = (int) ($attributes['total_minor'] ?? 0);
        if ($total < 0 || empty($attributes['items'])) {
            throw new \InvalidArgumentException('Quote total and items are invalid.');
        }

        return DB::transaction(fn (): Quote => Quote::query()->create(['team_id' => $attributes['team_id'] ?? null, 'customer_id' => $attributes['customer_id'] ?? null, 'quote_number' => $attributes['quote_number'] ?? 'QUO-'.strtoupper(bin2hex(random_bytes(5))), 'currency' => strtoupper($attributes['currency']), 'total_minor' => $total, 'items' => $attributes['items'], 'valid_until' => $attributes['valid_until'] ?? null, 'status' => 'draft']));
    }
}
