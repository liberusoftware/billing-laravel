<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Database\DatabaseManager;

final readonly class ExpireQuotes
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(?int $teamId = null): int
    {
        return $this->database->table('billing_order_quotes')->whereIn('status', ['sent', 'viewed'])->whereNotNull('valid_until')->where('valid_until', '<', now())->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))->update(['status' => 'expired', 'expired_at' => now(), 'updated_at' => now()]);
    }
}
