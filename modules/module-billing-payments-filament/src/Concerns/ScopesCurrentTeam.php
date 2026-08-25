<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopesCurrentTeam
{
    public static function getEloquentQuery(): Builder
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return parent::getEloquentQuery()->where(function (Builder $query) use ($team): void {
            if ($team === null) {
                $query->whereNull('team_id');
            } else {
                $query->whereNull('team_id')->orWhere('team_id', (int) $team);
            }
        });
    }
}
