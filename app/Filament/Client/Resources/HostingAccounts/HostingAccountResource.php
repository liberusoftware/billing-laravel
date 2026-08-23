<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\HostingAccounts;

use App\Filament\Client\Resources\HostingAccounts\Pages\ListHostingAccounts;
use App\Models\HostingAccount;
use App\Services\ControlPanels\CpanelClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class HostingAccountResource extends Resource
{
    #[Override]
    protected static ?string $model = HostingAccount::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server';

    #[Override]
    protected static ?string $navigationLabel = 'Hosting';

    // Customer-facing panel: access is governed by the email-scoped query below
    // (and clientOwns() on the action), not the staff HostingAccountPolicy.
    #[Override]
    public static function canViewAny(): bool
    {
        return true;
    }

    #[Override]
    public static function canView($record): bool
    {
        return true;
    }

    /**
     * A client owns a hosting account when it belongs to the Customer whose
     * email matches the logged-in user (mirrors the Client InvoiceResource
     * scoping — the Client panel authenticates a User, not a Customer).
     */
    public static function clientOwns(HostingAccount $account): bool
    {
        return $account->customer?->email === auth()->user()?->email;
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('domain')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('username')
                        ->searchable(),
                    TextColumn::make('control_panel')
                        ->badge(),
                    TextColumn::make('package'),
                    TextColumn::make('status')
                        ->badge()
                        ->color(
                            fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'suspended' => 'danger',
                                default => 'gray',
                            }
                        ),
                ]
            )
            ->recordActions(
                [
                    Action::make('open_cpanel')
                        ->label('Open cPanel')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->visible(fn (HostingAccount $record): bool => $record->control_panel === 'cpanel' && $record->isActive())
                        ->action(
                            function (HostingAccount $record) {
                                abort_unless(
                                    self::clientOwns($record),
                                    403
                                );

                                $client = app(CpanelClient::class);
                                $client->setServer($record->server);
                                $url = $client->createSsoSession($record->username);

                                if ($url === null) {
                                    Notification::make()
                                        ->title('Unable to open cPanel session. Please try again later.')
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                return redirect()->away($url);
                            }
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
            'index' => ListHostingAccounts::route('/'),
        ];
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas(
            'customer',
            fn (Builder $query) => $query->where('email', auth()->user()->email)
        );
    }
}
