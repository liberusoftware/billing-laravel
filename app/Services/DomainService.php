<?php

namespace App\Services;

use App\Models\HostingAccount;
use App\Models\Subscription;
use App\Services\Registrars\Contracts\RegistrarClient;
use App\Services\Registrars\EnomClient;
use App\Services\Registrars\ResellerClubClient;
use Exception;

class DomainService
{
    public function __construct(protected EnomClient $enomClient, protected ResellerClubClient $resellerClubClient) {}

    public function registerDomain(Subscription $subscription, $domainName, $registrar = 'enom')
    {
        $client = $this->clientFor($registrar);
        $result = $client->registerDomain(
            $domainName,
            $subscription->customer->id
        );

        if ($result) {
            $subscription->domain_name = $domainName;
            $subscription->domain_registrar = $registrar;
            $subscription->domain_expiration_date = $result['expiration_date'];
            $subscription->save();

            // Reflect the domain on an existing HostingAccount if one is already
            // provisioned. We do not create one here: username/package/status are
            // NOT NULL and are owned by the provisioning flow, not domain registration.
            $hostingAccount = HostingAccount::where(
                'subscription_id',
                $subscription->id
            )->first();
            if ($hostingAccount) {
                $hostingAccount->domain = $domainName;
                $hostingAccount->save();
            }
        }

        return $result;
    }

    public function renewDomain(Subscription $subscription, $period = 1)
    {
        $client = $this->clientFor($subscription->domain_registrar);
        $result = $client->renewDomain(
            $subscription->domain_name,
            $period
        );

        if ($result) {
            $subscription->domain_expiration_date = $result['new_expiration_date'];
            $subscription->save();
        }

        return $result;
    }

    public function transferDomain(Subscription $subscription, $domainName, $authCode, $newRegistrar)
    {
        $client = $this->clientFor($newRegistrar);
        $result = $client->transferDomain(
            $domainName,
            $authCode,
            $subscription->customer->id
        );

        if ($result) {
            $subscription->domain_name = $domainName;
            $subscription->domain_registrar = $newRegistrar;
            $subscription->domain_expiration_date = $result['expiration_date'];
            $subscription->domain_transfer_status = 'pending';
            $subscription->save();

            // Update HostingAccount
            $hostingAccount = HostingAccount::where(
                'subscription_id',
                $subscription->id
            )->first();
            if ($hostingAccount) {
                $hostingAccount->domain = $domainName;
                $hostingAccount->save();
            }
        }

        return $result;
    }

    /**
     * @return array<int, array{id: string, type: string, name: string, content: string, ttl: int}>
     */
    public function getDnsRecords(Subscription $subscription): array
    {
        return $this->clientFor($subscription->domain_registrar)
            ->getDnsRecords($subscription->domain_name);
    }

    /**
     * @param  array{type: string, name: string, content: string, ttl?: int}  $record
     */
    public function addDnsRecord(Subscription $subscription, array $record): bool
    {
        return $this->clientFor($subscription->domain_registrar)
            ->addDnsRecord($subscription->domain_name, $record);
    }

    public function deleteDnsRecord(Subscription $subscription, string $recordId): bool
    {
        return $this->clientFor($subscription->domain_registrar)
            ->deleteDnsRecord($subscription->domain_name, $recordId);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getWhoisContacts(Subscription $subscription): array
    {
        return $this->clientFor($subscription->domain_registrar)
            ->getWhoisContacts($subscription->domain_name);
    }

    /**
     * @param  array<string, array<string, string>>  $contacts
     */
    public function updateWhoisContacts(Subscription $subscription, array $contacts): bool
    {
        return $this->clientFor($subscription->domain_registrar)
            ->updateWhoisContacts($subscription->domain_name, $contacts);
    }

    public function clientFor($registrar): RegistrarClient
    {
        return match ($registrar) {
            'enom' => $this->enomClient,
            'resellerclub' => $this->resellerClubClient,
            default => throw new Exception("Unsupported domain registrar: $registrar"),
        };
    }
}
