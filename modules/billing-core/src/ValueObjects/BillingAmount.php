<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\ValueObjects;

use InvalidArgumentException;

final readonly class BillingAmount
{
    public function __construct(
        public int $minorUnits,
        public string $currency,
    ) {
        $currency = strtoupper(trim($currency));

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Currency must be a three-letter ISO code.');
        }

        if ($currency !== $this->currency) {
            throw new InvalidArgumentException('Currency must be normalized to uppercase.');
        }
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Amounts must use the same currency.');
        }
    }
}
