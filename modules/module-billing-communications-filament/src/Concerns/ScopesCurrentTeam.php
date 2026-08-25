<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopesCurrentTeam
{
    public static function getEloquentQuery(): Builder
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return parent::getEloquentQuery()->where('team_id', $team);
    }
}
