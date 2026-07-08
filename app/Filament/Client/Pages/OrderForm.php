<?php

declare(strict_types=1);

namespace App\Filament\Client\Pages;

use App\Models\OrderFormTemplate;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\OrderService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Override;

class OrderForm extends Page
{
    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    #[Override]
    protected string $view = 'filament.client.pages.order-form';

    public ?OrderFormTemplate $template = null;

    public ?int $selectedPlan = null;

    public string $billingCycle = 'monthly';

    /**
     * @var Collection<int, SubscriptionPlan>
     */
    public Collection $plans;

    public function mount(?string $templateSlug = null): void
    {
        $query = OrderFormTemplate::query()->where('is_active', true);
        $this->template = $templateSlug !== null
            ? $query->where('slug', $templateSlug)->firstOrFail()
            : $query->firstOrFail();

        $this->plans = SubscriptionPlan::query()
            ->whereIn('id', $this->template->offeredPlanIds())
            ->where('is_active', true)
            ->get();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make([
                Select::make('selectedPlan')
                    ->label('Select Plan')
                    ->options($this->plans->pluck('name', 'id'))
                    ->required(),
                Select::make('billingCycle')
                    ->label('Billing Cycle')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'semi-annually' => 'Semi-annually',
                        'annually' => 'Annually',
                    ])
                    ->required(),
            ]),
        ]);
    }

    public function placeOrder(): mixed
    {
        /** @var User $user */
        $user = auth()->user();
        $customer = $user->customer;

        if ($customer === null) {
            Notification::make()
                ->title('No customer account is linked to your login.')
                ->danger()
                ->send();

            return null;
        }

        try {
            app(OrderService::class)->placeOrder($this->template, $customer, [
                'subscription_plan_id' => (int) $this->selectedPlan,
                'billing_cycle' => $this->billingCycle,
            ]);
        } catch (InvalidArgumentException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            return null;
        }

        Notification::make()
            ->title('Order placed successfully.')
            ->success()
            ->send();

        return redirect()->route('filament.client.pages.dashboard');
    }
}
