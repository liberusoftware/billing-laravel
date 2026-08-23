<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

enum BillingCycle: string
{
    case OneTime = 'one-time';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case SemiAnnually = 'semi-annually';
    case Annually = 'annually';
    case Biennially = 'biennially';
    case Triennially = 'triennially';
    case Custom = 'custom';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $cycle) {
            $options[$cycle->value] = $cycle->label();
        }

        return $options;
    }

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'One-time',
            self::SemiAnnually => 'Semi-annually',
            default => ucfirst($this->value),
        };
    }

    public function advance(Carbon $date, ?int $customPeriodDays = null): Carbon
    {
        $next = $date->copy();

        return match ($this) {
            self::OneTime => $next,
            self::Daily => $next->addDay(),
            self::Weekly => $next->addWeek(),
            self::Monthly => $next->addMonth(),
            self::Quarterly => $next->addMonths(3),
            self::SemiAnnually => $next->addMonths(6),
            self::Annually => $next->addYear(),
            self::Biennially => $next->addYears(2),
            self::Triennially => $next->addYears(3),
            self::Custom => $next->addDays($this->validatedCustomPeriod($customPeriodDays)),
        };
    }

    public function priceMultiplier(): int
    {
        return match ($this) {
            self::Quarterly => 3,
            self::SemiAnnually => 6,
            self::Annually => 12,
            self::Biennially => 24,
            self::Triennially => 36,
            default => 1,
        };
    }

    public function isRecurring(): bool
    {
        return $this !== self::OneTime;
    }

    private function validatedCustomPeriod(?int $days): int
    {
        if ($days === null || $days < 1) {
            throw new InvalidArgumentException('A custom billing cycle requires custom_period_days of at least 1.');
        }

        return $days;
    }
}
