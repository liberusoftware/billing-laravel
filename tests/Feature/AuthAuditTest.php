<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_event_writes_audit_log(): void
    {
        $user = User::factory()->create();

        event(new Login('web', $user, false));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'login',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_logout_event_writes_audit_log(): void
    {
        $user = User::factory()->create();

        event(new Logout('web', $user));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'logout',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_failed_login_event_records_attempted_email(): void
    {
        event(new Failed('web', null, ['email' => 'attacker@example.com']));

        $this->assertDatabaseHas('audit_logs', ['event' => 'failed_login']);

        $log = AuditLog::where('event', 'failed_login')->firstOrFail();
        $this->assertSame('attacker@example.com', $log->old_values['email']);
    }
}
