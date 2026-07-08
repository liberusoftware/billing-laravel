<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Resources\LicenseResource\Pages\ListLicenses;
use App\Filament\Client\Resources\LicenseResource\Pages\ViewLicense;
use App\Models\License;
use App\Services\LicenseService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class LicenseResource extends Resource
{
    #[Override]
    protected static ?string $model = License::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    #[Override]
    protected static ?string $navigationLabel = 'Licenses';

    #[Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Section::make()
                        ->schema(
                            [
                                TextEntry::make('license_key')
                                    ->label('License Key')
                                    ->copyable(),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('max_instances')
                                    ->label('Max Instances'),
                                TextEntry::make('valid_until')
                                    ->date()
                                    ->placeholder('No expiry'),
                            ]
                        ),
                ]
            );
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('license_key')
                        ->label('License Key')
                        ->searchable()
                        ->copyable(),
                    TextColumn::make('status')
                        ->badge(),
                    TextColumn::make('max_instances')
                        ->label('Max Instances')
                        ->sortable(),
                    TextColumn::make('valid_until')
                        ->date()
                        ->placeholder('No expiry')
                        ->sortable(),
                ]
            )
            ->recordActions(
                [
                    ViewAction::make(),
                    Action::make('reissue')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->action(
                            fn (License $record) => app(LicenseService::class)->reissue($record)
                        ),
                ]
            )
            ->defaultSort(
                'created_at',
                'desc'
            );
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListLicenses::route('/'),
            'view' => ViewLicense::route('/{record}'),
        ];
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        // Licenses belong to a Customer (no client_id column exists). The client
        // panel authenticates a User, so scope to licenses of the Customer whose
        // email matches the logged-in user.
        return parent::getEloquentQuery()->whereHas(
            'customer',
            fn (Builder $query) => $query->where('email', auth()->user()->email)
        );
    }
}
