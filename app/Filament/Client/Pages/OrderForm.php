<?php

declare(strict_types=1);

namespace App\Filament\Client\Pages;

use App\Enums\BillingCycle;
use App\Filament\Client\Resources\Orders\OrderResource;
use App\Models\OrderFormTemplate;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\OrderService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Override;

class OrderForm extends Page
{
    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    #[Override]
    protected string $view = 'filament.client.pages.order-form';

    public ?OrderFormTemplate $template = null;

    #[Locked]
    public string $checkoutKey;

    public ?int $selectedPlan = null;

    public string $billingCycle = 'monthly';

    public ?int $customPeriodDays = null;

    public string $domain = '';

    public function requiresDomain(): bool
    {
        return $this->selectedPlan !== null && $this->template?->hostingProductIdForPlan($this->selectedPlan) !== null;
    }

    /** @var array<int, array{price: string, currency: string}> */
    #[Locked]
    public array $quotedPrices = [];

    public function orderTotal(): ?string
    {
        $quote = $this->currentQuote();

        return $quote === null ? null : number_format((float) $quote['total'], 2).' '.$quote['currency'];
    }

    /** @return array{total: string, currency: string}|null */
    private function currentQuote(): ?array
    {
        $price = $this->quotedPrices[$this->selectedPlan] ?? null;
        $cycle = BillingCycle::tryFrom($this->billingCycle);

        return $price !== null && $cycle !== null ? [
            'total' => number_format(round((float) $price['price'] * $cycle->priceMultiplier(), 2), 2, '.', ''),
            'currency' => $price['currency'],
        ] : null;
    }

    private function refreshQuotes(): void
    {
        $this->quotedPrices = SubscriptionPlan::query()
            ->whereIn('id', $this->template->offeredPlanIds())->where('is_active', true)->get()
            ->mapWithKeys(fn (SubscriptionPlan $plan): array => [$plan->id => ['price' => $plan->price, 'currency' => $plan->currency]])->all();
    }

    /**
     * @var Collection<int, SubscriptionPlan>
     */
    public Collection $plans;

    public function mount(?string $templateSlug = null): void
    {
        $this->checkoutKey = (string) Str::uuid();

        $query = OrderFormTemplate::query()->where('is_active', true);
        $this->template = $templateSlug !== null
            ? $query->where('slug', $templateSlug)->firstOrFail()
            : $query->firstOrFail();

        $this->plans = SubscriptionPlan::query()
            ->whereIn('id', $this->template->offeredPlanIds())
            ->where('is_active', true)
            ->get();
        $this->refreshQuotes();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make([
                Select::make('selectedPlan')
                    ->label('Select Plan')
                    ->options($this->plans->pluck('name', 'id'))
                    ->live()
                    ->required(),
                Select::make('billingCycle')
                    ->label('Billing Cycle')
                    ->options(fn (): array => $this->requiresDomain() ? BillingCycle::hostingOptions() : BillingCycle::options())
                    ->live()
                    ->required(),
                TextInput::make('domain')
                    ->label('Your existing domain')
                    ->helperText('Use a domain you already own. Registration and transfer are not included.')
                    ->visible(fn (): bool => $this->requiresDomain())
                    ->required(fn (): bool => $this->requiresDomain())
                    ->maxLength(253),
                TextInput::make('customPeriodDays')
                    ->label('Custom period (days)')
                    ->integer()
                    ->minValue(1)
                    ->visible(fn (): bool => $this->billingCycle === BillingCycle::Custom->value)
                    ->required(fn (): bool => $this->billingCycle === BillingCycle::Custom->value),
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
                'checkout_key' => $this->checkoutKey,
                'expected_quote' => $this->currentQuote(),
                'subscription_plan_id' => (int) $this->selectedPlan,
                'billing_cycle' => $this->billingCycle,
                'custom_period_days' => $this->customPeriodDays,
                'domain' => $this->domain,
                'domain_action' => 'existing',
            ]);
        } catch (InvalidArgumentException $e) {
            $this->refreshQuotes();
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

        return redirect()->to(OrderResource::getUrl(panel: 'client'));
    }
}
