<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Licenses\Tables;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Services\LicenseService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class LicensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('license_key')
                        ->searchable()
                        ->copyable(),
                    TextColumn::make('customer.name')
                        ->searchable(),
                    TextColumn::make('status')
                        ->badge(),
                    TextColumn::make('max_instances'),
                    TextColumn::make('valid_until')
                        ->date(),
                ]
            )
            ->filters(
                [
                    //
                ]
            )
            ->recordActions(
                [
                    EditAction::make(),
                    Action::make('reissue')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->requiresConfirmation()
                        ->action(function (License $record): void {
                            try {
                                app(LicenseService::class)->reissue($record, auth()->id());
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('toggleSuspend')
                        ->label(fn (License $record): string => $record->status === LicenseStatus::Suspended ? 'Unsuspend' : 'Suspend')
                        ->icon(Heroicon::OutlinedPower)
                        ->requiresConfirmation()
                        ->action(fn (License $record) => $record->update([
                            'status' => $record->status === LicenseStatus::Suspended
                                ? LicenseStatus::Active
                                : LicenseStatus::Suspended,
                        ])),
                    DeleteAction::make(),
                ]
            )
            ->toolbarActions(
                [
                    BulkActionGroup::make(
                        [
                            DeleteBulkAction::make(),
                        ]
                    ),
                ]
            );
    }
}
