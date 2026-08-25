<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Core\Actions\TransitionBillingAccount;
use Liberu\Billing\Core\Enums\BillingAccountStatus;
use Liberu\Billing\Core\Filament\Resources\BillingAccountResource\Pages\CreateBillingAccount;
use Liberu\Billing\Core\Filament\Resources\BillingAccountResource\Pages\EditBillingAccount;
use Liberu\Billing\Core\Filament\Resources\BillingAccountResource\Pages\ListBillingAccounts;
use Liberu\Billing\Core\Models\BillingAccount;

final class BillingAccountResource extends Resource
{
    protected static ?string $model = BillingAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('currency')->required()->length(3)->alpha()->default('USD'),
            Select::make('status')->options(collect(BillingAccountStatus::cases())->mapWithKeys(fn (BillingAccountStatus $status): array => [$status->value => ucfirst($status->value)])->all())->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('currency')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->actions([
            Action::make('status')->label('Update status')->form([FormSelect::make('status')->options(['active' => 'Active', 'suspended' => 'Suspended', 'closed' => 'Closed'])->required()])->action(function (BillingAccount $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransitionBillingAccount::class)->execute($record, BillingAccountStatus::from($data['status']));
            }),
            Action::make('edit')->url(fn (BillingAccount $record): string => self::getUrl('edit', ['record' => $record])),
            DeleteAction::make(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillingAccounts::route('/'),
            'create' => CreateBillingAccount::route('/create'),
            'edit' => EditBillingAccount::route('/{record}/edit'),
        ];
    }
}
