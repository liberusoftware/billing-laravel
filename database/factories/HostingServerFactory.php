<?php

namespace Database\Factories;

use App\Models\HostingServer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HostingServer>
 */
class HostingServerFactory extends Factory
{
    #[\Override]
    protected $model = HostingServer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Server',
            'hostname' => fake()->domainName(),
            'username' => fake()->userName(),
            'ip_address' => fake()->ipv4(),
            'control_panel' => fake()->randomElement(['cpanel', 'plesk', 'directadmin', 'virtualmin', 'virtualmin-gpl', 'virtualmin-pro', 'liberu']),
            'api_token' => fake()->sha256(),
            'api_url' => 'https://'.fake()->domainName(),
            'is_active' => true,
            'max_accounts' => fake()->numberBetween(100, 500),
            'active_accounts' => fake()->numberBetween(0, 50),
        ];
    }

    public function cpanel(): self
    {
        return $this->state(fn (array $attributes): array => [
            'control_panel' => 'cpanel',
        ]);
    }

    public function plesk(): self
    {
        return $this->state(fn (array $attributes): array => [
            'control_panel' => 'plesk',
        ]);
    }

    public function directadmin(): self
    {
        return $this->state(fn (array $attributes): array => [
            'control_panel' => 'directadmin',
        ]);
    }

    public function virtualmin(): self
    {
        return $this->state(fn (array $attributes): array => [
            'control_panel' => 'virtualmin',
        ]);
    }

    public function liberu(): self
    {
        return $this->state(fn (array $attributes): array => [
            'control_panel' => 'liberu',
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function atCapacity(): self
    {
        return $this->state(function (array $attributes): array {
            $maxAccounts = $attributes['max_accounts'] ?? 100;

            return [
                'active_accounts' => $maxAccounts,
            ];
        });
    }
}
