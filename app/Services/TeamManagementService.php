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
                $team = Team::defaultForRegistration();

                if ($team !== null && ! $user->belongsToTeam($team)) {
                    $user->teams()->attach(
                        $team,
                        ['role' => 'admin']
                    );
                }

                if ($team === null) {
                    /** @var Team $team */
                    $team = $user->ownedTeams()->create([
                        'name' => $user->name."'s Team",
                        'personal_team' => true,
                    ]);
                    $user->switchTeam($team);

                    return;
                }

                $user->forceFill(['current_team_id' => $team->id])->save();
            }
        );
    }
}
