<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Team;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * @property Schema $form
 */
class AccountSetup extends Page
{
    protected string $view = 'filament.app.pages.account-setup';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?string $navigationLabel = 'Account setup';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Account setup';

    public function mount(): void
    {
        $team = $this->team();
        $configuration = $team->setup_configuration ?? [];

        $this->form->fill([
            'team_name' => $team->name,
            'organisation_type' => $team->getRawOriginal('organisation_type') ?? 'company',
            'currency' => Arr::get($configuration, 'currency', 'USD'),
            'country' => Arr::get($configuration, 'country'),
            'timezone' => Arr::get($configuration, 'timezone', config('app.timezone')),
            'stripe_secret' => null,
            'paddle_token' => null,
            'tax_api_key' => null,
            'resellerclub_api_key' => null,
            'github_client_id' => null,
            'github_client_secret' => null,
            'google_client_id' => null,
            'google_client_secret' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Wizard\Step::make('Workspace')
                        ->description('Tell us about your business')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            Section::make('Workspace details')
                                ->description('These details are used for invoices, customer-facing pages, and defaults.')
                                ->schema([
                                    TextInput::make('team_name')
                                        ->label('Workspace name')
                                        ->required()
                                        ->maxLength(255),
                                    Select::make('organisation_type')
                                        ->label('Organisation type')
                                        ->options([
                                            'company' => 'Company',
                                            'reseller' => 'Reseller',
                                            'partner' => 'Partner',
                                            'white_label' => 'White label',
                                            'subsidiary' => 'Subsidiary',
                                            'franchise' => 'Franchise',
                                        ])
                                        ->required(),
                                    Select::make('country')
                                        ->options([
                                            'US' => 'United States',
                                            'GB' => 'United Kingdom',
                                            'CA' => 'Canada',
                                            'AU' => 'Australia',
                                            'NZ' => 'New Zealand',
                                            'IE' => 'Ireland',
                                        ])
                                        ->searchable()
                                        ->native(false),
                                    Select::make('currency')
                                        ->options([
                                            'USD' => 'USD — US Dollar',
                                            'GBP' => 'GBP — Pound Sterling',
                                            'EUR' => 'EUR — Euro',
                                            'CAD' => 'CAD — Canadian Dollar',
                                            'AUD' => 'AUD — Australian Dollar',
                                        ])
                                        ->required()
                                        ->native(false),
                                    Select::make('timezone')
                                        ->options(fn (): array => collect(\DateTimeZone::listIdentifiers())
                                            ->mapWithKeys(fn (string $timezone): array => [$timezone => $timezone])
                                            ->all())
                                        ->searchable()
                                        ->required()
                                        ->native(false),
                                ])
                                ->columns(2),
                        ]),
                    Wizard\Step::make('Connections')
                        ->description('Add optional provider credentials')
                        ->icon('heroicon-o-key')
                        ->schema([
                            Section::make('Billing and tax providers')
                                ->description('Credentials are encrypted at rest and scoped to this workspace. Leave a field blank to configure it later.')
                                ->schema([
                                    TextInput::make('stripe_secret')
                                        ->label('Stripe secret key')
                                        ->password()
                                        ->revealable()
                                        ->placeholder('sk_live_…'),
                                    TextInput::make('paddle_token')
                                        ->label('Paddle API token')
                                        ->password()
                                        ->revealable(),
                                    TextInput::make('tax_api_key')
                                        ->label('Tax service API key')
                                        ->password()
                                        ->revealable(),
                                    TextInput::make('resellerclub_api_key')
                                        ->label('ResellerClub API key')
                                        ->password()
                                        ->revealable(),
                                ])
                                ->columns(2),
                            Section::make('OAuth applications')
                                ->description('Optional OAuth client credentials for enabling social sign-in. Redirect URLs are shown in the provider documentation.')
                                ->schema([
                                    TextInput::make('github_client_id')
                                        ->label('GitHub client ID')
                                        ->helperText('Callback: '.url('/oauth/github/callback')),
                                    TextInput::make('github_client_secret')
                                        ->label('GitHub client secret')
                                        ->password()
                                        ->revealable(),
                                    TextInput::make('google_client_id')
                                        ->label('Google client ID')
                                        ->helperText('Callback: '.url('/oauth/google/callback')),
                                    TextInput::make('google_client_secret')
                                        ->label('Google client secret')
                                        ->password()
                                        ->revealable(),
                                ])
                                ->columns(2),
                        ]),
                    Wizard\Step::make('Review')
                        ->description('Confirm your defaults')
                        ->icon('heroicon-o-check-circle')
                        ->schema([
                            Section::make('Ready to go')
                                ->description('You can return to Account setup at any time to update workspace defaults or add a provider.')
                                ->schema([
                                    TextInput::make('setup_summary')
                                        ->label('Next step')
                                        ->default('Create your first customer, product, or invoice.')
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),
                        ]),
                ])
                    ->persistStepInQueryString()
                    ->skippable(),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $team = $this->team();
        $configuration = $team->setup_configuration ?? [];
        $credentials = array_filter([
            'stripe_secret' => $state['stripe_secret'] ?? null,
            'paddle_token' => $state['paddle_token'] ?? null,
            'tax_api_key' => $state['tax_api_key'] ?? null,
            'resellerclub_api_key' => $state['resellerclub_api_key'] ?? null,
            'github_client_id' => $state['github_client_id'] ?? null,
            'github_client_secret' => $state['github_client_secret'] ?? null,
            'google_client_id' => $state['google_client_id'] ?? null,
            'google_client_secret' => $state['google_client_secret'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $team->forceFill([
            'name' => $state['team_name'],
            'organisation_type' => $state['organisation_type'],
            'setup_configuration' => [
                ...$configuration,
                'country' => $state['country'] ?? null,
                'currency' => $state['currency'],
                'timezone' => $state['timezone'],
                ...$credentials,
            ],
            'setup_completed_at' => now(),
        ])->save();

        Notification::make()
            ->title('Workspace setup saved')
            ->body('Your workspace defaults and provider credentials are securely stored.')
            ->success()
            ->send();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    private function team(): Team
    {
        $team = Team::query()->find(Auth::user()?->current_team_id);

        abort_unless($team !== null, 403, 'A current workspace is required.');
        abort_unless(Auth::user()->ownsTeam($team), 403);

        return $team;
    }
}
