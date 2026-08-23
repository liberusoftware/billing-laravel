<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\InvoiceStatusChanged;
use App\Listeners\RenewDomainOnPayment;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Override;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    #[Override]
    protected $listen = [
        InvoiceStatusChanged::class => [
            RenewDomainOnPayment::class,
        ],
    ];

    public static function logRegistration(Registered $event): void
    {
        app(AuditLogService::class)->log('registration', $event->user instanceof Model ? $event->user : null);
    }

    public static function logLogin(Login $event): void
    {
        app(AuditLogService::class)->log('login', $event->user instanceof Model ? $event->user : null);
    }

    public static function logLogout(Logout $event): void
    {
        app(AuditLogService::class)->log('logout', $event->user instanceof Model ? $event->user : null);
    }

    public static function logFailedLogin(Failed $event): void
    {
        app(AuditLogService::class)->log('failed_login', null, ['email' => $event->credentials['email'] ?? null]);
    }

    /**
     * Register any events for your application.
     *
     * Auth audit listeners are wired here as closures rather than in $listen:
     * this provider extends ServiceProvider (constructor needs $app), so the
     * [self::class, 'method'] listener form would fail to container-resolve.
     * SendEmailVerificationNotification for Registered is left to the base
     * provider (configureEmailVerification), so we don't double-send it.
     */
    #[Override]
    public function boot(): void
    {
        Event::listen(Registered::class, function (Registered $event): void {
            self::logRegistration($event);
        });
        Event::listen(Login::class, function (Login $event): void {
            self::logLogin($event);
        });
        Event::listen(Logout::class, function (Logout $event): void {
            self::logLogout($event);
        });
        Event::listen(Failed::class, function (Failed $event): void {
            self::logFailedLogin($event);
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    #[Override]
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
