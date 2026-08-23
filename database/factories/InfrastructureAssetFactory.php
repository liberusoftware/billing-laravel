<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InfrastructureAssetType;
use App\Models\InfrastructureAsset;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InfrastructureAsset> */
class InfrastructureAssetFactory extends Factory
{
    protected $model = InfrastructureAsset::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'asset_type' => fake()->randomElement(InfrastructureAssetType::cases()),
            'name' => fake()->unique()->domainWord(),
            'hostname' => fake()->domainName(),
            'serial_number' => fake()->unique()->uuid(),
            'vendor' => fake()->company(),
            'model' => fake()->bothify('Model-###'),
            'location' => fake()->city(),
            'status' => 'active',
        ];
    }
}
