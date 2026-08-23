<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property float $base_price
 * @property float $enom_cost
 * @property string $markup_type
 * @property float $markup_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'base_price',
    'markup_type',
    'markup_value',
    'enom_cost',
])]
class Tld extends Model
{
    #[Override]
    protected function casts(): array
    {
        return [
            'base_price' => 'float',
            'markup_value' => 'float',
            'enom_cost' => 'float',
        ];
    }

    public function calculatePrice()
    {
        if ($this->markup_type === 'percentage') {
            return $this->enom_cost * (1 + $this->markup_value / 100);
        }

        if ($this->markup_type === 'fixed') {
            return $this->enom_cost + $this->markup_value;
        }

        return $this->base_price;
    }
}
