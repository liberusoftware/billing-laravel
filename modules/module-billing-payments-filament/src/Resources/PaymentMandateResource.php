<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Payments\Actions\TransitionPaymentMandate;
use Liberu\Billing\Payments\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Payments\Filament\Resources\PaymentMandateResource\Pages\CreatePaymentMandate;
use Liberu\Billing\Payments\Filament\Resources\PaymentMandateResource\Pages\ListPaymentMandates;
use Liberu\Billing\Payments\Models\PaymentMandate;

final class PaymentMandateResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Billing Operations';

    use ScopesCurrentTeam;

    protected static ?string $model = PaymentMandate::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('payment_method_id')->required()->integer()->minValue(1), TextInput::make('provider')->required()->maxLength(64), TextInput::make('provider_reference')->maxLength(255)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('payment_method_id'), TextColumn::make('status')->badge(), TextColumn::make('provider_reference')])->actions([
            Action::make('status')->label('Update status')->form([Select::make('status')->options([
                'pending' => 'Pending',
                'active' => 'Active',
                'revoked' => 'Revoked',
                'expired' => 'Expired',
            ])->required()])->action(function (PaymentMandate $record, array $data, TransitionPaymentMandate $transition): void {
                Gate::authorize('update', $record);
                $transition->execute($record, $data['status']);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPaymentMandates::route('/'), 'create' => CreatePaymentMandate::route('/create')];
    }
}
