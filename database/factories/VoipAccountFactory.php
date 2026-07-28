<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VoipAccountStatus;
use App\Enums\VoipPlatform;
use App\Models\Customer;
use App\Models\Team;
use App\Models\VoipAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoipAccount>
 */
class VoipAccountFactory extends Factory
{
    protected $model = VoipAccount::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'customer_id' => Customer::factory(),
            'platform' => fake()->randomElement(VoipPlatform::cases()),
            'status' => VoipAccountStatus::Pending,
            'sip_username' => fake()->unique()->userName(),
            'sip_secret' => fake()->password(16),
            'caller_id' => fake()->e164PhoneNumber(),
            'credit_limit' => 100,
            'max_concurrent_calls' => 1,
            'international_enabled' => false,
        ];
    }
}
