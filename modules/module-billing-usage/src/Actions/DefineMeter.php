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
        $code = strtolower(trim((string) ($attributes['code'] ?? '')));
        $name = trim((string) ($attributes['name'] ?? $code));
        $unit = trim((string) ($attributes['unit'] ?? 'unit'));
        $currency = strtoupper((string) ($attributes['currency'] ?? ''));
        $threshold = $attributes['threshold'] ?? null;
        if ($code === '' || $name === '' || $unit === '' || ! preg_match('/^[a-z][a-z0-9_.-]*$/', $code) || ! preg_match('/^[A-Z]{3}$/', $currency) || (int) ($attributes['unit_price_minor'] ?? 0) < 0 || ($threshold !== null && (! is_int($threshold) && ! is_float($threshold) && ! is_numeric($threshold) || (float) $threshold < 0))) {
            throw new \InvalidArgumentException('Meter code, currency, and price are invalid.');
        }

        return $this->database->transaction(fn (): Meter => Meter::query()->create(['team_id' => $attributes['team_id'] ?? null, 'name' => $name, 'code' => $code, 'unit' => $unit, 'unit_price_minor' => $attributes['unit_price_minor'], 'currency' => $currency, 'threshold' => $threshold, 'active' => true, 'metadata' => $attributes['metadata'] ?? []]));
    }
}
