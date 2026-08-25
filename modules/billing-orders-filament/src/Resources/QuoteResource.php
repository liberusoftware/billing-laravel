<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Orders\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Orders\Filament\Resources\QuoteResource\Pages\CreateQuote;
use Liberu\Billing\Orders\Filament\Resources\QuoteResource\Pages\ListQuotes;
use Liberu\Billing\Orders\Models\Quote;

final class QuoteResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = Quote::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('customer_id')->integer()->minValue(1),
            TextInput::make('currency')->required()->length(3)->default('USD'),
            TextInput::make('total_minor')->required()->integer()->minValue(0),
            Textarea::make('items')->required()->default('[]')->helperText('JSON array'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('quote_number')->searchable(), TextColumn::make('total_minor'), TextColumn::make('currency'), TextColumn::make('status')->badge()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListQuotes::route('/'), 'create' => CreateQuote::route('/create')];
    }
}
