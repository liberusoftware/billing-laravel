<?php

namespace App\Filament\Pages;

use App\Enums\BillingCycle;
use App\Models\SubscriptionPlan;
use App\Services\BillingService;
use BackedEnum;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Override;

class SubscriptionPlansPage extends Page
{
    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    #[Override]
    protected string $view = 'filament.pages.subscription-plans';

    public $selectedPlan;

    public $billingCycle = 'monthly';

    public ?int $customPeriodDays = null;

    public $plans;

    public function mount(): void
    {
        $this->plans = SubscriptionPlan::where(
            'is_active',
            true
        )->get();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Section::make()
                        ->schema(
                            [
                                Select::make('selectedPlan')
                                    ->label('Select Plan')
                                    ->options(
                                        $this->plans?->pluck(
                                            'name',
                                            'id'
                                        ) ?? []
                                    )
                                    ->required(),
                                Select::make('billingCycle')
                                    ->label('Billing Cycle')
                                    ->options(
                                        BillingCycle::options()
                                    )
                                    ->live()
                                    ->required(),
                                TextInput::make('customPeriodDays')
                                    ->label('Custom period (days)')
                                    ->integer()
                                    ->minValue(1)
                                    ->visible(fn (): bool => $this->billingCycle === BillingCycle::Custom->value)
                                    ->required(fn (): bool => $this->billingCycle === BillingCycle::Custom->value),
                            ]
                        ),
                ]
            );
    }

    public function subscribe(): mixed
    {
        $plan = SubscriptionPlan::findOrFail($this->selectedPlan);
        $customer = auth()->user()->customer;

        if ($customer === null) {
            Notification::make()
                ->title('No customer account is linked to your login.')
                ->danger()
                ->send();

            return null;
        }

        try {
            $billingService = app(BillingService::class);

            $subscription = $billingService->createSubscription(
                $customer,
                $plan,
                $this->billingCycle,
                $this->customPeriodDays
            );

            return redirect()->route(
                'filament.pages.checkout',
                [
                    'subscription' => $subscription->id,
                ]
            );
        } catch (Exception) {
            Notification::make()
                ->title('Error creating subscription')
                ->danger()
                ->send();

            return null;
        }
    }
}
