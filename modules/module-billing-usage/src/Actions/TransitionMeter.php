<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Usage\Events\MeterStatusChanged;
use Liberu\Billing\Usage\Models\Meter;

final readonly class TransitionMeter
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Meter $meter, bool $active): Meter
    {
        return $this->database->transaction(function () use ($meter, $active): Meter {
            $locked = Meter::query()->lockForUpdate()->findOrFail($meter->getKey());
            if ((bool) $locked->active === $active) {
                return $locked;
            }

            $locked->update(['active' => $active]);
            $locked->refresh();
            MeterStatusChanged::dispatch($locked);

            return $locked;
        });
    }
}
