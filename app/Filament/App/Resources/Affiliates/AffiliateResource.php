<?php

namespace App\Filament\App\Resources\Affiliates;

use App\Filament\App\Resources\Affiliates\Pages\CreateAffiliate;
use App\Filament\App\Resources\Affiliates\Pages\EditAffiliate;
use App\Filament\App\Resources\Affiliates\Pages\ListAffiliates;
use App\Models\Affiliate;
use App\Services\AffiliateReportingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

class AffiliateResource extends Resource
{
    #[Override]
    protected static ?string $model = Affiliate::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Select::make('user_id')
                        ->relationship(
                            'user',
                            'name'
                        )
                        ->required(),
                    TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true),
                    TextInput::make('commission_rate')
                        ->required()
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->maxValue(100),
                    Select::make('status')
                        ->options(
                            [
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ]
                        )
                        ->required(),
                    TextInput::make('total_earnings')
                        ->disabled()
                        ->label('Total Earnings')
                        ->formatStateUsing(
                            fn ($state): string => '$'.number_format(
                                $state,
                                2
                            )
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
                    TextColumn::make('user.name'),
                    TextColumn::make('code'),
                    TextColumn::make('commission_rate'),
                    TextColumn::make('status'),
                    TextColumn::make('total_earnings')
                        ->money('usd')
                        ->sortable(),
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
                    Action::make('generateReport')
                        ->label('Generate Report')
                        ->icon('heroicon-o-document-report')
                        ->action(
                            function (Affiliate $record, AffiliateReportingService $reportingService) {
                                $report = $reportingService->generateReport(
                                    $record,
                                    now()->subMonth(),
                                    now()
                                );

                                // Here you can return the report data or redirect to a report view
                                return redirect()->route(
                                    'affiliate.report',
                                    $report
                                );
                            }
                        ),
                ]
            )
            ->toolbarActions(
                [
                    DeleteBulkAction::make(),
                ]
            );
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListAffiliates::route('/'),
            'create' => CreateAffiliate::route('/create'),
            'edit' => EditAffiliate::route('/{record}/edit'),
        ];
    }
}
