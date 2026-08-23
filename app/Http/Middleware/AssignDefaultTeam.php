<?php

namespace App\Http\Middleware;

use App\Models\Team;
use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

class AssignDefaultTeam
{
    public function handle(Request $request, Closure $next)
    {
        if (! Filament::getTenant() && auth()->check()) {
            /** @var User $user */
            $user = auth()->user();
            $defaultTeam = $user->currentTeam ?? $user->ownedTeams()->first();
            if (! $defaultTeam) {
                /** @var Team $defaultTeam */
                $defaultTeam = $user->ownedTeams()->create(
                    [
                        'name' => $user->name."'s Team",
                        'personal_team' => true,
                    ]
                );
                $user->current_team_id = $defaultTeam->id;
                $user->save();
            }
            Filament::setTenant($defaultTeam);
        }

        return $next($request);
    }
}
