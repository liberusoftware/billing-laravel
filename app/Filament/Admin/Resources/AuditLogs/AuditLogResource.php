<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AuditLogs;

use App\Models\AuditLog;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class AuditLogResource extends Resource
{
    #[Override]
    protected static ?string $model = AuditLog::class;

    // Audit logs are global/system records, not team-owned; opt out of the
    // admin panel's tenancy so creating one under an active tenant (e.g. via a
    // model observer) doesn't try to resolve a non-existent team relationship.
    protected static bool $isScopedToTenant = false;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-list';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable(),
                    TextColumn::make('user.name')
                        ->searchable(),
                    TextColumn::make('event')
                        ->searchable(),
                    TextColumn::make('auditable_type')
                        ->searchable(),
                    TextColumn::make('ip_address')
                        ->searchable(),
                ]
            )
            ->filters(
                [
                    SelectFilter::make('event'),
                    Filter::make('created_at')
                        ->schema(
                            [
                                DatePicker::make('from'),
                                DatePicker::make('until'),
                            ]
                        ),
                ]
            )
            ->defaultSort(
                'created_at',
                'desc'
            );
    }
}
