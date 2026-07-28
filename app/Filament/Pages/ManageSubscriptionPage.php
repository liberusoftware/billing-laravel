<?php

namespace App\Filament\Pages;

use App\Enums\BillingCycle;
use App\Models\Products_Service;
use App\Models\Subscription;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Override;

class ManageSubscriptionPage extends Page
{
    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    #[Override]
    protected string $view = 'filament.pages.manage-subscription';

    public $subscription;

    public $selectedProduct;

    public $renewalPeriod;

    public ?int $customPeriodDays = null;

    public $autoRenew;

    public $startDate;

    public function mount(): void
    {
        $this->subscription = Auth::user()->subscription;
        if ($this->subscription) {
            $this->selectedProduct = $this->subscription->product_service_id;
            $this->renewalPeriod = $this->subscription->renewal_period;
            $this->customPeriodDays = $this->subscription->custom_period_days;
            $this->autoRenew = $this->subscription->auto_renew;
            $this->startDate = $this->subscription->start_date;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Section::make()
                        ->schema(
                            [
                                Grid::make(2)
                                    ->schema(
                                        [
                                            Select::make('selectedProduct')
                                                ->label('Service')
                                                ->options(
                                                    Products_Service::pluck(
                                                        'name',
                                                        'id'
                                                    )
                                                )
                                                ->required(),

                                            Select::make('renewalPeriod')
                                                ->label('Billing Cycle')
                                                ->options(BillingCycle::options())
                                                ->live()
                                                ->required(),

                                            TextInput::make('customPeriodDays')
                                                ->label('Custom period (days)')
                                                ->integer()
                                                ->minValue(1)
                                                ->visible(fn (): bool => $this->renewalPeriod === BillingCycle::Custom->value)
                                                ->required(fn (): bool => $this->renewalPeriod === BillingCycle::Custom->value),

                                            DatePicker::make('startDate')
                                                ->label('Start Date')
                                                ->required(),

                                            Toggle::make('autoRenew')
                                                ->label('Auto Renew')
                                                ->default(true),
                                        ]
                                    ),
                            ]
                        ),
                ]
            );
    }

    public function save(): void
    {
        $product = Products_Service::findOrFail((int) $this->selectedProduct);

        if (! $this->subscription) {
            $this->subscription = new Subscription;
        }

        $this->subscription->fill(
            [
                'customer_id' => Auth::user()->customer->id,
                'product_service_id' => $this->selectedProduct,
                'start_date' => $this->startDate,
                'renewal_period' => $this->renewalPeriod,
                'custom_period_days' => $this->renewalPeriod === BillingCycle::Custom->value
                    ? $this->customPeriodDays
                    : null,
                'auto_renew' => $this->renewalPeriod === BillingCycle::OneTime->value
                    ? false
                    : $this->autoRenew,
                'price' => $product->price,
                'currency' => $this->subscription->currency ?: 'USD',
                'status' => 'active',
            ]
        );

        $this->subscription->save();

        Notification::make()
            ->title('Subscription updated successfully')
            ->success()
            ->send();
    }

    public function cancel(): void
    {
        $this->subscription?->cancel();

        Notification::make()
            ->title('Subscription cancelled successfully')
            ->success()
            ->send();
    }

    public function resume(): void
    {
        $this->subscription?->resume();

        Notification::make()
            ->title('Subscription resumed successfully')
            ->success()
            ->send();
    }
}
