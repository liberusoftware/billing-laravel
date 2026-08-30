<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Domains\Events\DomainTldUpserted;
use Liberu\Billing\Domains\Models\DomainTld;

final class UpsertDomainTld
{
    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): DomainTld
    {
        $name = '.'.ltrim(strtolower(trim((string) ($attributes['name'] ?? ''))), '.');
        $markupType = (string) ($attributes['markup_type'] ?? 'none');
        $basePrice = (float) ($attributes['base_price'] ?? -1);
        $markupValue = (float) ($attributes['markup_value'] ?? 0);
        if (! preg_match('/^\.[a-z0-9-]{2,63}$/', $name) || $basePrice < 0 || $markupValue < 0 || ! in_array($markupType, ['none', 'percentage', 'fixed'], true)) {
            throw new InvalidArgumentException('TLD pricing details are invalid.');
        }

        return DB::transaction(function () use ($name, $attributes, $basePrice, $markupType, $markupValue): DomainTld {
            $tld = DomainTld::query()->updateOrCreate(['name' => $name], [
                'registrar_cost' => $attributes['registrar_cost'] ?? null,
                'base_price' => $basePrice,
                'markup_type' => $markupType,
                'markup_value' => $markupValue,
                'enabled' => (bool) ($attributes['enabled'] ?? true),
            ]);
            DomainTldUpserted::dispatch($tld);

            return $tld;
        });
    }
}
