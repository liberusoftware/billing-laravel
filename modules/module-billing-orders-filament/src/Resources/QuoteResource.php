<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Orders\Actions\ConvertQuoteToOrder;
use Liberu\Billing\Orders\Actions\TransitionQuote;
use Liberu\Billing\Orders\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Orders\Filament\Resources\QuoteResource\Pages\CreateQuote;
use Liberu\Billing\Orders\Filament\Resources\QuoteResource\Pages\ListQuotes;
use Liberu\Billing\Orders\Models\Quote;

final class QuoteResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Customers & Sales';

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
        return $table->columns([TextColumn::make('quote_number')->searchable(), TextColumn::make('total_minor'), TextColumn::make('currency'), TextColumn::make('status')->badge()])->actions([
            Action::make('transition')->form([Select::make('status')->options(['sent' => 'Sent', 'viewed' => 'Viewed', 'accepted' => 'Accepted', 'declined' => 'Declined'])->required()])->action(function (Quote $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransitionQuote::class)->execute($record, $data['status']);
            }),
            Action::make('convert')->label('Convert to order')->requiresConfirmation()->action(function (Quote $record): void {
                Gate::authorize('update', $record);
                app(ConvertQuoteToOrder::class)->execute($record);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListQuotes::route('/'), 'create' => CreateQuote::route('/create')];
    }
}
