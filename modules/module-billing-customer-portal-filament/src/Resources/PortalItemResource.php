<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\CustomerPortal\Actions\TransitionPortalItem;
use Liberu\Billing\CustomerPortal\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalItemResource\Pages\CreatePortalItem;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalItemResource\Pages\ListPortalItems;
use Liberu\Billing\CustomerPortal\Models\PortalItem;

final class PortalItemResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Customers & Sales';

    use ScopesCurrentTeam;

    protected static ?string $model = PortalItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('type')->required()->maxLength(32), TextInput::make('subject')->required()->maxLength(255), TextInput::make('customer_id')->integer()->minValue(1)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('type')->badge(), TextColumn::make('subject')->searchable(), TextColumn::make('customer_id'), TextColumn::make('status')->badge()])->actions([
            Action::make('status')->label('Update status')->form([Select::make('status')->options(['open' => 'Open', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'failed' => 'Failed'])->required()])->action(function (PortalItem $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransitionPortalItem::class)->handle($record, $data['status']);
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListPortalItems::route('/'), 'create' => CreatePortalItem::route('/create')];
    }
}
