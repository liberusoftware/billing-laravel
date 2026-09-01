<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament\Resources\QuoteResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Orders\Actions\CreateQuote as CreateQuoteAction;
use Liberu\Billing\Orders\Filament\Resources\QuoteResource;

final class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['items'] = $this->decodeItems((string) ($data['items'] ?? '[]'));
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $data['team_id'] = $team === null ? null : (int) $team;

        return app(CreateQuoteAction::class)->execute($data);
    }

    /** @return array<int,mixed> */
    private function decodeItems(string $items): array
    {
        $decoded = json_decode($items, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new \InvalidArgumentException('Quote items must be a JSON array.');
        }

        return array_values($decoded);
    }
}
