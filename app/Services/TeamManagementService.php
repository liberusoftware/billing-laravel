<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Liberu\Foundation\Organizations\Models\Team;

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
                $team = Team::query()->where('is_default_for_registration', true)->first();

                if ($team !== null && ! $user->belongsToTeam($team)) {
                    $user->teams()->attach(
                        $team,
                        ['role' => 'admin']
                    );
                }

                if ($team !== null) {
                    $user->forceFill(['current_team_id' => $team->id])->save();
                }
            }
        );
    }
}
