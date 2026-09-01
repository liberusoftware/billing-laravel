<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Billing\Core\Models\BillingContact;
use Liberu\Billing\Core\Models\BillingCurrency;
use Liberu\Billing\Core\Models\BillingSequence;
use Liberu\Billing\Core\Models\BillingSetting;
use Liberu\Billing\Core\Models\BillingTaxExemption;
use Liberu\Billing\Core\Models\BillingTaxProfile;
use Liberu\Billing\Core\Models\BillingTerm;
use UnitEnum;

abstract class BillingCoreResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static UnitEnum|string|null $navigationGroup = 'Billing Operations';

    public static function form(Schema $schema): Schema
    {
        $model = static::getModel();
        $fields = match ($model) {
            BillingContact::class => [TextInput::make('name')->required()->maxLength(255), TextInput::make('email')->email()->maxLength(255), TextInput::make('phone')->maxLength(50)],
            BillingCurrency::class => [TextInput::make('code')->required()->length(3)->alpha(), TextInput::make('name')->required()->maxLength(100), TextInput::make('decimal_places')->integer()->minValue(0)->maxValue(8)->default(2), Checkbox::make('enabled')->default(true)],
            BillingTaxProfile::class => [TextInput::make('name')->required()->maxLength(255), TextInput::make('rate')->required()->numeric()->minValue(0)->maxValue(100), TextInput::make('jurisdiction')->maxLength(100), TextInput::make('threshold_amount')->numeric()->minValue(0), TextInput::make('threshold_rate')->numeric()->minValue(0)->maxValue(100), Checkbox::make('inclusive'), Checkbox::make('enabled')->default(true)],
            BillingTaxExemption::class => [TextInput::make('customer_id')->required()->numeric()->minValue(1), TextInput::make('expires_at')->type('datetime-local'), TextInput::make('reason')->maxLength(255), Checkbox::make('enabled')->default(true)],
            BillingSequence::class => [TextInput::make('name')->required()->maxLength(100), TextInput::make('prefix')->maxLength(30), TextInput::make('next_number')->integer()->minValue(1)->default(1)],
            BillingTerm::class => [TextInput::make('name')->required()->maxLength(100), TextInput::make('due_days')->integer()->minValue(0)->maxValue(3650), Checkbox::make('default')],
            BillingSetting::class => [KeyValue::make('values')->required()],
            default => [],
        };

        return $schema->components($fields);
    }

    public static function table(Table $table): Table
    {
        $columns = match (static::getModel()) {
            BillingContact::class => [TextColumn::make('name')->searchable()->sortable(), TextColumn::make('email')->searchable(), TextColumn::make('phone')],
            BillingCurrency::class => [TextColumn::make('code')->badge()->sortable(), TextColumn::make('name')->searchable(), TextColumn::make('enabled')->badge()],
            BillingTaxProfile::class => [TextColumn::make('name')->searchable(), TextColumn::make('rate')->suffix('%')->sortable(), TextColumn::make('jurisdiction'), TextColumn::make('threshold_rate')->suffix('%')],
            BillingTaxExemption::class => [TextColumn::make('customer_id')->sortable(), TextColumn::make('expires_at')->dateTime(), TextColumn::make('enabled')->badge()],
            BillingSequence::class => [TextColumn::make('name')->searchable(), TextColumn::make('prefix'), TextColumn::make('next_number')->sortable()],
            BillingTerm::class => [TextColumn::make('name')->searchable(), TextColumn::make('due_days')->suffix(' days')->sortable(), TextColumn::make('default')->badge()],
            BillingSetting::class => [TextColumn::make('team_id')->sortable(), TextColumn::make('updated_at')->dateTime()->sortable()],
            default => [TextColumn::make('id')->sortable()],
        };

        return $table->columns($columns)->defaultSort('id', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $teamId = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return parent::getEloquentQuery()->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $teamId));
    }
}
