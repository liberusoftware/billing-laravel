<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AuditLogService::class);
    }

    public function test_log_creates_audit_log_entry(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        new \stdClass;

        $this->service->log('test_action', null, null, ['key' => 'value']);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'test_action',
        ]);
    }

    public function test_service_instantiates_correctly(): void
    {
        $this->assertInstanceOf(AuditLogService::class, $this->service);
    }
}
