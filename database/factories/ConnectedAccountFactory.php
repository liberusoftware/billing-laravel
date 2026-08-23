<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ConnectedAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConnectedAccount>
 */
class ConnectedAccountFactory extends Factory
{
    #[\Override]
    protected $model = ConnectedAccount::class;

    public function definition(): array
    {
        return [
            'provider' => fake()->randomElement(['github', 'google', 'facebook', 'twitter']),
            'provider_id' => fake()->numerify('########'),
            'token' => Str::random(432),
            'refresh_token' => Str::random(432),
        ];
    }
}
