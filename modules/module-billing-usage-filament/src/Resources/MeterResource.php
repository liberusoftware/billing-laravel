<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Usage\Actions\CheckUsageThreshold;
use Liberu\Billing\Usage\Actions\RateUsage;
use Liberu\Billing\Usage\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Usage\Filament\Resources\MeterResource\Pages\CreateMeter;
use Liberu\Billing\Usage\Filament\Resources\MeterResource\Pages\ListMeters;
use Liberu\Billing\Usage\Models\Meter;

final class MeterResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = Meter::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), TextInput::make('code')->required(), TextInput::make('unit')->required(), TextInput::make('unit_price_minor')->numeric()->required(), TextInput::make('currency')->length(3)->required(), TextInput::make('threshold')->numeric(), Toggle::make('active')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('code')->sortable(), TextColumn::make('unit'), TextColumn::make('unit_price_minor'), TextColumn::make('currency'), TextColumn::make('active')->badge()])->actions([
            Action::make('rate')
                ->label('Rate usage')
                ->form([TextInput::make('quantity')->numeric()->minValue(0)->required()])
                ->action(function (Meter $record, array $data): void {
                    $quantity = (float) $data['quantity'];
                    $amount = app(RateUsage::class)->execute($record, $quantity);
                    $reached = app(CheckUsageThreshold::class)->execute($record, $quantity);
                    Notification::make()->title('Usage rated')->body("{$amount} minor units; threshold ".($reached ? 'reached' : 'not reached').'.')->success()->send();
                }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListMeters::route('/'), 'create' => CreateMeter::route('/create')];
    }
}
