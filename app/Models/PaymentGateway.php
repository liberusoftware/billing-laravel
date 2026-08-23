<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int|null $team_id
 * @property string $name
 * @property string $api_key
 * @property string $secret_key
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Payment> $payments
 */
#[Fillable([
    'name',
    'api_key',
    'secret_key',
    'is_active',
])]
class PaymentGateway extends Model
{
    use HasTeam;

    #[Override]
    protected function casts(): array
    {

        return [
            'is_active' => 'boolean',
            'api_key' => 'encrypted',
            'secret_key' => 'encrypted',
        ];

    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
