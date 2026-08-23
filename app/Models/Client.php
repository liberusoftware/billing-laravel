<?php

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
 * @property string $email
 * @property string|null $phone
 * @property string|null $company
 * @property string|null $address
 * @property string|null $notes
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, ClientNote> $notes
 */
#[Fillable([
    'team_id',
    'name',
    'email',
    'phone',
    'address',
    'company',
    'notes',
    'status',
])]
class Client extends Model
{
    use HasTeam;

    #[Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class);
    }
}
