<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Usage\Models\Meter;

final readonly class DefineMeter
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(array $attributes): Meter
    {
        $currency = strtoupper((string) ($attributes['currency'] ?? ''));
        if ((string) ($attributes['code'] ?? '') === '' || ! preg_match('/^[A-Z]{3}$/', $currency) || (int) ($attributes['unit_price_minor'] ?? 0) < 0) {
            throw new \InvalidArgumentException('Meter code, currency, and price are invalid.');
        }

        return $this->database->transaction(fn (): Meter => Meter::query()->create(['team_id' => $attributes['team_id'] ?? null, 'name' => $attributes['name'] ?? $attributes['code'], 'code' => $attributes['code'], 'unit' => $attributes['unit'] ?? 'unit', 'unit_price_minor' => $attributes['unit_price_minor'], 'currency' => $currency, 'threshold' => $attributes['threshold'] ?? null, 'active' => true, 'metadata' => $attributes['metadata'] ?? []]));
    }
}
