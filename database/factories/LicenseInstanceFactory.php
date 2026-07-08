<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\License;
use App\Models\LicenseInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicenseInstance>
 */
class LicenseInstanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'identifier' => fake()->domainName(),
            'ip_address' => fake()->ipv4(),
            'last_validated_at' => now(),
        ];
    }
}
