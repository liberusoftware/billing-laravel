<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Isp\Actions\TransitionAccessService;
use Liberu\Billing\Isp\Filament\Resources\AccessServiceResource\Pages\CreateAccessService;
use Liberu\Billing\Isp\Filament\Resources\AccessServiceResource\Pages\ListAccessServices;
use Liberu\Billing\Isp\Models\AccessService;

final class AccessServiceResource extends Resource
{
    protected static ?string $model = AccessService::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255), TextInput::make('status')->required()->default('active')->maxLength(32)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()])->actions([
            Action::make('transition')->label('Update status')->form([Select::make('status')->options(['pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled', 'failed' => 'Failed'])->required()])->action(function (AccessService $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransitionAccessService::class)->handle($record, $data['status']);
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListAccessServices::route('/'), 'create' => CreateAccessService::route('/create')];
    }
}
