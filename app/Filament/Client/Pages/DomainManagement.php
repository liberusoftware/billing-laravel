<?php

namespace App\Filament\Client\Pages;

use App\Models\Subscription;
use App\Services\DomainService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class DomainManagement extends Page implements HasTable
{
    use InteractsWithTable;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    #[Override]
    protected static ?string $navigationLabel = 'Domains';

    protected static ?string $title = 'Domain DNS & WHOIS';

    #[Override]
    protected string $view = 'filament.client.pages.domain-management';

    /**
     * Subscriptions with a registered domain owned by the authenticated client.
     * Scoped by customer email (the client panel authenticates a User; there is no
     * client_id column) — mirrors InvoiceResource. This is the ownership guard:
     * every table record (and therefore every action) is restricted to this set.
     */
    public static function ownedDomainsQuery(): Builder
    {
        return Subscription::query()
            ->whereNotNull('domain_name')
            ->whereHas('customer', fn (Builder $query) => $query->where('email', auth()->user()->email));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::ownedDomainsQuery())
            ->columns([
                TextColumn::make('domain_name')
                    ->label('Domain')
                    ->searchable(),
                TextColumn::make('domain_registrar')
                    ->label('Registrar')
                    ->badge(),
                TextColumn::make('domain_expiration_date')
                    ->label('Expires')
                    ->date(),
            ])
            ->recordActions([
                $this->addDnsRecordAction(),
                $this->deleteDnsRecordAction(),
                $this->whoisAction(),
            ]);
    }

    private function addDnsRecordAction(): Action
    {
        return Action::make('addDnsRecord')
            ->label('Add DNS record')
            ->icon('heroicon-o-plus')
            ->schema([
                Select::make('type')
                    ->options(['A' => 'A', 'AAAA' => 'AAAA', 'CNAME' => 'CNAME', 'MX' => 'MX', 'TXT' => 'TXT'])
                    ->required(),
                TextInput::make('name')->required(),
                TextInput::make('content')->label('Value')->required(),
                TextInput::make('ttl')->numeric()->default(3600)->required(),
            ])
            ->action(function (Subscription $record, array $data): void {
                app(DomainService::class)->addDnsRecord($record, $data);
                Notification::make()->title('DNS record added')->success()->send();
            });
    }

    private function deleteDnsRecordAction(): Action
    {
        // The Select doubles as the read view of current records (ponytail: a
        // dedicated read-only DNS table can be added if clients ask for one).
        return Action::make('deleteDnsRecord')
            ->label('Delete DNS record')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->schema([
                Select::make('record_id')
                    ->label('Record')
                    ->options(fn (Subscription $record): array => collect(app(DomainService::class)->getDnsRecords($record))
                        ->mapWithKeys(fn (array $r): array => [$r['id'] => "{$r['type']} {$r['name']} → {$r['content']}"])
                        ->all())
                    ->required(),
            ])
            ->action(function (Subscription $record, array $data): void {
                app(DomainService::class)->deleteDnsRecord($record, (string) $data['record_id']);
                Notification::make()->title('DNS record deleted')->success()->send();
            });
    }

    private function whoisAction(): Action
    {
        return Action::make('whois')
            ->label('WHOIS contacts')
            ->icon('heroicon-o-identification')
            ->fillForm(fn (Subscription $record): array => app(DomainService::class)->getWhoisContacts($record))
            ->schema([
                Section::make('Registrant')->schema([
                    TextInput::make('registrant.name')->label('Name'),
                    TextInput::make('registrant.organization')->label('Organization'),
                    TextInput::make('registrant.email')->label('Email')->email(),
                    TextInput::make('registrant.phone')->label('Phone'),
                ]),
            ])
            ->action(function (Subscription $record, array $data): void {
                app(DomainService::class)->updateWhoisContacts($record, $data);
                Notification::make()->title('WHOIS contacts updated')->success()->send();
            });
    }
}
