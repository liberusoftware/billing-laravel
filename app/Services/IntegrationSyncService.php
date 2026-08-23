<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BusinessConnector;
use App\Enums\ConnectorType;
use App\Models\Customer;
use App\Models\ExternalConnection;
use App\Models\ExternalSyncRecord;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IntegrationSyncService
{
    public function __construct(private readonly BusinessConnector $connector) {}

    /** @param array<string, mixed> $payload */
    public function push(ExternalConnection $connection, Model $model, string $resource, array $payload): ExternalSyncRecord
    {
        if (! $connection->is_active) {
            throw new InvalidArgumentException('The external connection is inactive.');
        }

        $localId = (string) $model->getKey();
        $record = $connection->syncRecords()
            ->where('resource_type', $resource)
            ->where('local_id', $localId)
            ->first();
        $checksum = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        if ($record !== null && $record->checksum === $checksum && $record->status === 'synchronized') {
            return $record;
        }

        $remoteId = $this->connector->push($connection, $resource, $localId, $payload, $record?->remote_id);

        return $connection->syncRecords()->updateOrCreate(
            ['resource_type' => $resource, 'local_id' => $localId],
            [
                'remote_id' => $remoteId,
                'checksum' => $checksum,
                'status' => 'synchronized',
                'last_pushed_at' => now(),
            ]
        );
    }

    public function synchronizeCustomer(Customer $customer): int
    {
        $connections = $this->connections($customer->team_id, ConnectorType::Crm);
        $count = 0;

        foreach ($connections as $connection) {
            $this->push($connection, $customer, 'customers', [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone_number,
                'address' => [
                    'line' => $customer->address,
                    'city' => $customer->city,
                    'state' => $customer->state,
                    'postal_code' => $customer->postal_code,
                    'country' => $customer->country,
                ],
                'lifecycle_status' => $customer->lifecycle_status->value,
            ]);
            foreach ($customer->contacts as $contact) {
                $this->push($connection, $contact, 'contacts', [
                    'customer_id' => $customer->id,
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'email' => $contact->email,
                    'type' => $contact->contact_type->value,
                ]);
            }
            $count++;
        }

        return $count;
    }

    public function synchronizeInvoice(Invoice $invoice): int
    {
        return $this->pushToAccounting($invoice, 'invoices', [
            'number' => $invoice->invoice_number,
            'customer_id' => $invoice->customer_id,
            'issue_date' => $invoice->issue_date->toDateString(),
            'due_date' => $invoice->due_date->toDateString(),
            'amount' => $invoice->total_amount,
            'currency' => $invoice->currency,
            'status' => $invoice->status,
        ]);
    }

    public function synchronizePayment(Payment $payment): int
    {
        return $this->pushToAccounting($payment, 'payments', [
            'invoice_id' => $payment->invoice_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'paid_at' => $payment->payment_date->toISOString(),
        ]);
    }

    public function synchronizeTaxRate(TaxRate $taxRate): int
    {
        return $this->pushToAccounting($taxRate, 'tax-rates', [
            'name' => $taxRate->name,
            'rate' => $taxRate->rate,
            'country' => $taxRate->country,
            'state' => $taxRate->state,
        ]);
    }

    public function convertLead(Lead $lead): Customer
    {
        return DB::transaction(function () use ($lead): Customer {
            $customer = Customer::query()->create([
                'team_id' => $lead->team_id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone_number' => $lead->phone,
                'lifecycle_status' => 'active',
            ]);
            $lead->update(['status' => 'converted']);
            $this->synchronizeCustomer($customer);

            return $customer;
        });
    }

    /** @param array{amount: float|int|string, currency?: string, name?: string} $opportunity */
    public function billOpportunity(Customer $customer, array $opportunity): Invoice
    {
        $invoice = Invoice::query()->create([
            'team_id' => $customer->team_id,
            'customer_id' => $customer->id,
            'invoice_number' => 'OPP-'.now()->format('YmdHis').'-'.$customer->id,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'total_amount' => round((float) $opportunity['amount'], 2),
            'currency' => $opportunity['currency'] ?? 'USD',
            'status' => 'pending',
            'notes' => $opportunity['name'] ?? 'CRM opportunity',
        ]);
        $this->synchronizeInvoice($invoice);

        return $invoice;
    }

    /** @return Collection<int, ExternalConnection> */
    private function connections(?int $teamId, ConnectorType $type)
    {
        return ExternalConnection::query()
            ->where('team_id', $teamId)
            ->where('connector_type', $type)
            ->where('is_active', true)
            ->get();
    }

    /** @param array<string, mixed> $payload */
    private function pushToAccounting(Model $model, string $resource, array $payload): int
    {
        $teamId = $model->getAttribute('team_id');
        $connections = $this->connections(is_numeric($teamId) ? (int) $teamId : null, ConnectorType::Accounting);

        foreach ($connections as $connection) {
            $this->push($connection, $model, $resource, $payload);
        }

        return $connections->count();
    }
}
