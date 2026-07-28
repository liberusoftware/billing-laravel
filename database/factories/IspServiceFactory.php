<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BroadbandTechnology;
use App\Enums\IspServiceStatus;
use App\Enums\RadiusPlatform;
use App\Models\Customer;
use App\Models\IspService;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IspService>
 */
class IspServiceFactory extends Factory
{
    protected $model = IspService::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'customer_id' => Customer::factory(),
            'technology' => fake()->randomElement(BroadbandTechnology::cases()),
            'status' => IspServiceStatus::Pending,
            'radius_platform' => fake()->randomElement(RadiusPlatform::cases()),
            'radius_username' => fake()->unique()->userName(),
            'radius_secret' => fake()->password(16),
            'download_limit_bps' => 100_000_000,
            'upload_limit_bps' => 20_000_000,
            'monthly_data_limit_bytes' => null,
        ];
    }
}
