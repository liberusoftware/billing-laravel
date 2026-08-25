<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;

final readonly class UpdateBillingRecord
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string,mixed> $attributes */
    public function execute(Model $record, array $attributes): Model
    {
        return $this->database->transaction(function () use ($record, $attributes): Model {
            $record->fill($attributes);
            $record->save();

            return $record->refresh();
        });
    }
}
