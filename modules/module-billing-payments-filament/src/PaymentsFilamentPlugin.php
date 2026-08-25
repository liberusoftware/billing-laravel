<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Billing\Payments\Filament\Resources\PaymentAllocationResource;
use Liberu\Billing\Payments\Filament\Resources\PaymentDisputeResource;
use Liberu\Billing\Payments\Filament\Resources\PaymentMandateResource;
use Liberu\Billing\Payments\Filament\Resources\PaymentMethodResource;
use Liberu\Billing\Payments\Filament\Resources\PaymentReconciliationResource;
use Liberu\Billing\Payments\Filament\Resources\PaymentRefundResource;
use Liberu\Billing\Payments\Filament\Resources\PaymentResource;

final class PaymentsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'module-billing-payments-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PaymentResource::class, PaymentMethodResource::class, PaymentMandateResource::class, PaymentAllocationResource::class, PaymentRefundResource::class, PaymentDisputeResource::class, PaymentReconciliationResource::class]);
    }

    public function boot(Panel $panel): void {}
}
