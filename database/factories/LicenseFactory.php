<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LicenseStatus;
use App\Models\Customer;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'license_key' => 'LIC-'.strtoupper(Str::random(5)).'-'.strtoupper(Str::random(5)).'-'.strtoupper(Str::random(5)).'-'.strtoupper(Str::random(5)),
            'status' => LicenseStatus::Active,
            'max_instances' => 1,
        ];
    }
}
