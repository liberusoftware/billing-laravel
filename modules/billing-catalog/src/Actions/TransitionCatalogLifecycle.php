<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;
use Liberu\Billing\Catalog\Enums\CatalogStatus;
use Liberu\Billing\Catalog\Models\CatalogRecord;

final readonly class TransitionCatalogLifecycle
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(CatalogRecord $record, CatalogStatus $status): CatalogRecord
    {
        $current = $record->status;
        if ($current === CatalogStatus::Archived && $status !== CatalogStatus::Archived) {
            throw ValidationException::withMessages(['status' => 'Archived catalog records cannot be reactivated.']);
        }
        if ($current === CatalogStatus::Draft && $status === CatalogStatus::Archived) {
            throw ValidationException::withMessages(['status' => 'A draft must be activated before it can be archived.']);
        }

        return $this->database->transaction(function () use ($record, $status): CatalogRecord {
            $record->update(['status' => $status]);

            return $record->refresh();
        });
    }
}
