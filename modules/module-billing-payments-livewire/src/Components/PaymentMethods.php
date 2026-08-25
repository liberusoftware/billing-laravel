<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Payments\Actions\CreatePaymentMandate;
use Liberu\Billing\Payments\Actions\CreatePaymentMethod;
use Liberu\Billing\Payments\Models\PaymentMandate;
use Liberu\Billing\Payments\Models\PaymentMethod;
use Livewire\Component;

final class PaymentMethods extends Component
{
    public string $type = 'card';

    public string $provider = '';

    public string $displayName = '';

    public string $lastFour = '';

    public int $customerId = 0;

    public bool $isDefault = false;

    public ?int $selectedPaymentMethodId = null;

    public string $mandateProvider = '';

    public string $mandateProviderReference = '';

    public string $mandateStatus = 'pending';

    public function createMethod(CreatePaymentMethod $create): void
    {
        Gate::authorize('create', PaymentMethod::class);
        $this->validate([
            'type' => ['required', 'string', 'max:50'],
            'provider' => ['required', 'string', 'max:50'],
            'displayName' => ['nullable', 'string', 'max:255'],
            'lastFour' => ['nullable', 'digits:4'],
            'customerId' => ['nullable', 'integer', 'min:0'],
        ]);
        $create->execute($this->methodAttributes());
        $this->reset(['displayName', 'lastFour', 'customerId', 'isDefault']);
        session()->flash('module-billing-payments-methods-message', __('Payment method created.'));
    }

    public function createMandate(CreatePaymentMandate $create): void
    {
        Gate::authorize('create', PaymentMandate::class);
        $this->validate([
            'selectedPaymentMethodId' => ['required', 'integer', 'min:1'],
            'mandateProvider' => ['required', 'string', 'max:50'],
            'mandateProviderReference' => ['nullable', 'string', 'max:255'],
            'mandateStatus' => ['required', 'string', 'max:50'],
        ]);
        $method = PaymentMethod::query()->whereKey($this->selectedPaymentMethodId)->where('team_id', $this->teamId())->firstOrFail();
        $create->execute([
            'team_id' => $this->teamId(),
            'customer_id' => $method->getAttribute('customer_id'),
            'payment_method_id' => $method->id,
            'provider' => $this->mandateProvider,
            'provider_reference' => $this->mandateProviderReference ?: null,
            'status' => $this->mandateStatus,
        ]);
        $this->reset(['selectedPaymentMethodId', 'mandateProvider', 'mandateProviderReference']);
        $this->mandateStatus = 'pending';
        session()->flash('module-billing-payments-methods-message', __('Payment mandate created.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', PaymentMethod::class);
        $team = $this->teamId();

        return view('module-billing-payments-livewire::methods', [
            'methods' => PaymentMethod::query()->where('team_id', $team)->latest()->get(),
            'mandates' => PaymentMandate::query()->where('team_id', $team)->latest()->get(),
        ]);
    }

    /** @return array<string,mixed> */
    private function methodAttributes(): array
    {
        return [
            'team_id' => $this->teamId(),
            'customer_id' => $this->customerId > 0 ? $this->customerId : null,
            'type' => $this->type,
            'provider' => $this->provider,
            'display_name' => $this->displayName ?: null,
            'last_four' => $this->lastFour ?: null,
            'is_default' => $this->isDefault,
        ];
    }

    private function teamId(): int
    {
        return (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
    }
}
