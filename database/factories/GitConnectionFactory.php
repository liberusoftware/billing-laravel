<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GitProvider;
use App\Models\GitConnection;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GitConnection> */
class GitConnectionFactory extends Factory
{
    protected $model = GitConnection::class;

    public function definition(): array
    {
        $provider = fake()->randomElement(GitProvider::cases());

        return [
            'team_id' => Team::factory(),
            'provider' => $provider,
            'name' => fake()->unique()->company(),
            'base_url' => match ($provider) {
                GitProvider::GitHub => 'https://api.github.com',
                GitProvider::GitLab => 'https://gitlab.com/api/v4',
                GitProvider::Bitbucket => 'https://api.bitbucket.org/2.0',
            },
            'access_token' => fake()->sha256(),
            'webhook_secret' => fake()->sha256(),
            'is_active' => true,
        ];
    }
}
