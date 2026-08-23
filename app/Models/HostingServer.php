<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property string $hostname
 * @property string|null $username
 * @property string|null $ip_address
 * @property string $control_panel
 * @property string $api_token
 * @property string $api_url
 * @property bool $is_active
 * @property int $max_accounts
 * @property int $active_accounts
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, HostingAccount> $hostingAccounts
 * @property-read Team|null $team
 */
#[Fillable([
    'name',
    'hostname',
    'control_panel',
    'api_token',
    'api_url',
    'username',
    'ip_address',
    'is_active',
    'max_accounts',
    'active_accounts',
])]
class HostingServer extends Model
{
    use HasFactory;
    use HasTeam;

    #[Override]
    protected function casts(): array
    {

        return [
            'is_active' => 'boolean',
            'max_accounts' => 'integer',
            'active_accounts' => 'integer',
        ];

    }

    public function hostingAccounts(): HasMany
    {
        return $this->hasMany(HostingAccount::class);
    }

    public function isAtCapacity(): bool
    {
        return $this->max_accounts > 0 && $this->active_accounts >= $this->max_accounts;
    }

    public function hasCapacity(): bool
    {
        return ! $this->isAtCapacity();
    }

    public function getUsagePercentage(): float|int
    {
        if ($this->max_accounts === 0) {
            return 0;
        }

        return ($this->active_accounts / $this->max_accounts) * 100;
    }
}
