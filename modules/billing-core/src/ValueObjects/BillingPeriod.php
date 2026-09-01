<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\ValueObjects;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class BillingPeriod
{
    public function __construct(
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
    ) {
        if ($endsAt->lessThan($startsAt)) {
            throw new InvalidArgumentException('A billing period must end on or after it starts.');
        }
    }

    public static function monthly(CarbonImmutable $startsAt): self
    {
        return new self($startsAt, $startsAt->addMonth()->subSecond());
    }

    public function contains(CarbonImmutable $moment): bool
    {
        return $moment->betweenIncluded($this->startsAt, $this->endsAt);
    }
}
