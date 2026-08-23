<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\IspService;
use App\Models\RadiusSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RadiusSession>
 */
class RadiusSessionFactory extends Factory
{
    protected $model = RadiusSession::class;

    public function definition(): array
    {
        return [
            'isp_service_id' => IspService::factory(),
            'accounting_session_id' => fake()->uuid(),
            'nas_identifier' => fake()->domainName(),
            'ip_address' => fake()->ipv4(),
            'started_at' => now()->subHour(),
            'ended_at' => null,
            'input_bytes' => fake()->numberBetween(0, 1_000_000),
            'output_bytes' => fake()->numberBetween(0, 10_000_000),
            'session_seconds' => 3600,
        ];
    }
}
