<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Subscriptions\Actions\UpdateEntitlementState;
use Liberu\Billing\Subscriptions\Models\Subscription;
use Livewire\Component;

final class SubscriptionEntitlements extends Component
{
    public ?int $selectedSubscriptionId = null;

    public string $entitlements = '{}';

    public function updateEntitlements(UpdateEntitlementState $update): void
    {
        $this->validate(['selectedSubscriptionId' => ['required', 'integer', 'min:1'], 'entitlements' => ['required', 'json']]);
        $subscription = Subscription::query()->whereKey($this->selectedSubscriptionId)->where('team_id', $this->teamId())->firstOrFail();
        Gate::authorize('update', $subscription);
        $payload = json_decode($this->entitlements, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($payload)) {
            throw new \InvalidArgumentException('Entitlements must be a JSON object.');
        }
        $update->execute($subscription, $payload);
        $this->reset(['selectedSubscriptionId', 'entitlements']);
        $this->entitlements = '{}';
        session()->flash('module-billing-subscriptions-entitlements-message', __('Subscription entitlements updated.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Subscription::class);
        $team = $this->teamId();

        return view('module-billing-subscriptions-livewire::entitlements', ['subscriptions' => Subscription::query()->where('team_id', $team)->latest()->get()]);
    }

    private function teamId(): int
    {
        return (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
    }
}
