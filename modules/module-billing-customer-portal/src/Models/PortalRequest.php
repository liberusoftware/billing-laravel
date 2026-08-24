<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PortalRequest extends Model
{
    use SoftDeletes;

    protected $table = 'billing_customer_portal_records';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }
}
