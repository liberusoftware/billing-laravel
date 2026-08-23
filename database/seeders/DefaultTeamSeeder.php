<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Team;
use Illuminate\Database\Seeder;

class DefaultTeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Team::create([
            'id' => 1,
            'name' => 'default',
            'personal_team' => false,
            'user_id' => 1,
        ]);

        // Branch::create([
        //     'name' => 'Default Branch',
        //     'address' => '123 Main St, City, Country',
        //     'phone_number' => '+1234567890',
        //     'team_id' => $team->id,
        // ]);
    }
}
