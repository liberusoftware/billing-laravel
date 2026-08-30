<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Payments\Models\PaymentMandate;

final readonly class TransitionPaymentMandate
{
    private const STATUSES = ['pending', 'active', 'revoked', 'expired'];

    public function __construct(private DatabaseManager $database) {}

    public function execute(PaymentMandate $mandate, string $status): PaymentMandate
    {
        $status = strtolower(trim($status));
        if (! in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Payment mandate status is invalid.');
        }

        return $this->database->transaction(function () use ($mandate, $status): PaymentMandate {
            $locked = PaymentMandate::query()->lockForUpdate()->findOrFail($mandate->getKey());
            if (in_array($locked->status, ['revoked', 'expired'], true) && $status !== $locked->status) {
                throw new \LogicException('A terminal payment mandate cannot be reactivated.');
            }
            $locked->update(['status' => $status]);

            return $locked->refresh();
        });
    }
}
