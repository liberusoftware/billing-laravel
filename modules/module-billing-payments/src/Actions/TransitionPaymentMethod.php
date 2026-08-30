<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Payments\Enums\PaymentMethodStatus;
use Liberu\Billing\Payments\Models\PaymentMethod;

final readonly class TransitionPaymentMethod
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(PaymentMethod $method, PaymentMethodStatus $status): PaymentMethod
    {
        return $this->database->transaction(function () use ($method, $status): PaymentMethod {
            $locked = PaymentMethod::query()->lockForUpdate()->findOrFail($method->getKey());
            $locked->update(['status' => $status]);

            return $locked->refresh();
        });
    }
}
