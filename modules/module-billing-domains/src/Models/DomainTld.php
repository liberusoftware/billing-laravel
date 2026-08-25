<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Models;

use Illuminate\Database\Eloquent\Model;

final class DomainTld extends Model
{
    protected $table = 'billing_domain_tlds';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['registrar_cost' => 'decimal:4', 'base_price' => 'decimal:4', 'markup_value' => 'decimal:4', 'enabled' => 'boolean'];
    }

    public function calculatePrice(): float
    {
        $basePrice = (float) $this->base_price;
        $markup = (float) $this->markup_value;

        return match ($this->markup_type) {
            'percentage' => round($basePrice * (1 + $markup / 100), 2),
            'fixed' => round($basePrice + $markup, 2),
            default => round($basePrice, 2),
        };
    }
}
