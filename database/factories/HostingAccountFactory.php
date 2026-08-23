<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\HostingAccount;
use App\Models\HostingServer;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HostingAccount>
 */
class HostingAccountFactory extends Factory
{
    #[\Override]
    protected $model = HostingAccount::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'subscription_id' => Subscription::factory(),
            'hosting_server_id' => HostingServer::factory(),
            'control_panel' => fake()->randomElement(['cpanel', 'plesk', 'directadmin', 'virtualmin', 'liberu']),
            'username' => fake()->userName(),
            'domain' => fake()->domainName(),
            'package' => fake()->randomElement(['basic', 'standard', 'premium', 'business']),
            'status' => 'active',
            'price' => fake()->randomFloat(2, 5, 100),
            'addons' => [],
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'active',
        ]);
    }

    public function suspended(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'suspended',
        ]);
    }

    public function terminated(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'terminated',
        ]);
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
        ]);
    }

    public function withAddons(array $addons): self
    {
        return $this->state(fn (array $attributes): array => [
            'addons' => $addons,
        ]);
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
}
