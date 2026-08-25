<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final readonly class CreateBillingRecord
{
    public function __construct(private DatabaseManager $database) {}

    /** @param class-string<Model> $modelClass @param array<string,mixed> $attributes */
    public function execute(string $modelClass, array $attributes): Model
    {
        if ((int) ($attributes['team_id'] ?? 0) < 1) {
            throw new InvalidArgumentException('A team is required.');
        }

        return $this->database->transaction(fn (): Model => $modelClass::query()->create($attributes));
    }
}
