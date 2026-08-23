<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ConnectorType;
use App\Models\ExternalConnection;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExternalConnection> */
class ExternalConnectionFactory extends Factory
{
    protected $model = ExternalConnection::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'connector_type' => fake()->randomElement(ConnectorType::cases()),
            'provider' => fake()->word(),
            'name' => fake()->unique()->company(),
            'base_url' => 'https://integration.example.test/api',
            'access_token' => fake()->sha256(),
            'signing_secret' => fake()->sha256(),
            'resource_mappings' => [],
            'event_subscriptions' => ['*'],
            'is_active' => true,
        ];
    }
}
