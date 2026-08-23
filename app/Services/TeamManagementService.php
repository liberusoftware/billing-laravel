<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeamManagementService
{
    public function createPersonalTeamForUser(User $user): void
    {
        /** @var Team $team */
        $team = $user->ownedTeams()->create([
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]);

        $user->switchTeam($team);
    }

    public function assignUserToDefaultTeam(User $user): void
    {
        DB::transaction(
            function () use ($user): void {
                $team = Team::firstOrCreate(
                    [
                        'name' => $user->name."'s Team",
                        'user_id' => $user->id,
                    ],
                    ['personal_team' => true]
                );

                if (! $user->belongsToTeam($team)) {
                    $user->teams()->attach(
                        $team,
                        ['role' => 'admin']
                    );
                }

                $user->forceFill(['current_team_id' => $team->id])->save();
            }
        );
    }
}
