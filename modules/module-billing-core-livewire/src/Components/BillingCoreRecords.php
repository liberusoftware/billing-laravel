<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Livewire\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Core\Actions\CreateBillingRecord;
use Liberu\Billing\Core\Actions\UpdateBillingRecord;
use Liberu\Billing\Core\Models\BillingContact;
use Liberu\Billing\Core\Models\BillingCurrency;
use Liberu\Billing\Core\Models\BillingSequence;
use Liberu\Billing\Core\Models\BillingSetting;
use Liberu\Billing\Core\Models\BillingTaxExemption;
use Liberu\Billing\Core\Models\BillingTaxProfile;
use Liberu\Billing\Core\Models\BillingTerm;
use Liberu\Billing\Core\Queries\ListBillingRecords;
use Livewire\Component;

final class BillingCoreRecords extends Component
{
    public string $type = 'contacts';

    public string $name = '';

    public string $email = '';

    public string $code = 'USD';

    public string $rate = '0';

    public int|string $customerId = '';

    public string $expiresAt = '';

    public string $reason = '';

    public string $prefix = '';

    public int $dueDays = 0;

    public int $nextNumber = 1;

    public string $valuesJson = '{}';

    public bool $showCreate = false;

    public ?int $selectedRecordId = null;

    public function updatedType(): void
    {
        $this->resetValidation();
        $this->reset(['name', 'email', 'prefix', 'valuesJson', 'showCreate', 'customerId', 'expiresAt', 'reason']);
        $this->code = 'USD';
        $this->rate = '0';
        $this->dueDays = 0;
        $this->nextNumber = 1;
    }

    public function save(CreateBillingRecord $create): void
    {
        $model = $this->modelClass();
        Gate::authorize('create', $model);
        $this->validate($this->rules());
        $attributes = $this->attributes();
        $attributes['team_id'] = $this->teamId();
        $create->execute($model, $attributes);
        $this->showCreate = false;
        $this->reset(['name', 'email', 'prefix', 'valuesJson']);
        session()->flash('billing-core-record-message', __('Record created.'));
    }

    public function update(UpdateBillingRecord $update): void
    {
        $model = $this->modelClass();
        $record = $model::query()->whereKey($this->selectedRecordId)->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $this->teamId()))->firstOrFail();
        Gate::authorize('update', $record);
        $this->validate($this->rules());
        $update->execute($record, $this->attributes());
        $this->reset(['selectedRecordId', 'name', 'email', 'prefix', 'valuesJson']);
        session()->flash('billing-core-record-message', __('Record updated.'));
    }

    public function render(ListBillingRecords $list): View
    {
        $model = $this->modelClass();
        Gate::authorize('viewAny', $model);
        $records = $list->execute($model, $this->teamId());

        return view('module-billing-core-livewire::billing-core-records', ['records' => $records->items()]);
    }

    /** @return class-string<Model> */
    private function modelClass(): string
    {
        return match ($this->type) {
            'contacts' => BillingContact::class,
            'currencies' => BillingCurrency::class,
            'tax-profiles' => BillingTaxProfile::class,
            'tax-exemptions' => BillingTaxExemption::class,
            'sequences' => BillingSequence::class,
            'terms' => BillingTerm::class,
            'settings' => BillingSetting::class,
            default => throw new \InvalidArgumentException('Unknown Billing Core record type.'),
        };
    }

    /** @return array<string, array<int, string>> */
    private function rules(): array
    {
        return match ($this->type) {
            'contacts' => ['name' => ['required', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255']],
            'currencies' => ['code' => ['required', 'string', 'size:3', 'alpha']],
            'tax-profiles' => ['name' => ['required', 'string', 'max:255'], 'rate' => ['required', 'numeric', 'between:0,100']],
            'tax-exemptions' => ['customerId' => ['required', 'integer', 'min:1'], 'expiresAt' => ['nullable', 'date'], 'reason' => ['nullable', 'string', 'max:255']],
            'sequences' => ['name' => ['required', 'string', 'max:100'], 'nextNumber' => ['required', 'integer', 'min:1']],
            'terms' => ['name' => ['required', 'string', 'max:100'], 'dueDays' => ['required', 'integer', 'min:0', 'max:3650']],
            'settings' => ['valuesJson' => ['required', 'json']],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private function attributes(): array
    {
        return match ($this->type) {
            'contacts' => ['name' => $this->name, 'email' => $this->email ?: null],
            'currencies' => ['code' => strtoupper($this->code), 'name' => strtoupper($this->code), 'decimal_places' => 2, 'enabled' => true],
            'tax-profiles' => ['name' => $this->name, 'rate' => $this->rate, 'enabled' => true],
            'tax-exemptions' => ['customer_id' => (int) $this->customerId, 'expires_at' => $this->expiresAt ?: null, 'reason' => $this->reason ?: null, 'enabled' => true],
            'sequences' => ['name' => $this->name, 'prefix' => $this->prefix ?: null, 'next_number' => $this->nextNumber],
            'terms' => ['name' => $this->name, 'due_days' => $this->dueDays],
            'settings' => ['values' => json_decode($this->valuesJson, true, 512, JSON_THROW_ON_ERROR)],
            default => [],
        };
    }

    private function teamId(): int
    {
        return (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
    }
}
