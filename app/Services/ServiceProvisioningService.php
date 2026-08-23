<?php

namespace App\Services;

use App\Models\HostingAccount;
use App\Models\Subscription;
use Exception;

class ServiceProvisioningService
{
    public function __construct(protected HostingService $hostingService) {}

    public function provisionService(Subscription $subscription): HostingAccount|array
    {
        $service = $subscription->productService;

        return match ($service->type) {
            'hosting' => $this->provisionHosting($subscription),
            'domain' => $this->provisionDomain(),
            'email' => $this->provisionEmail(),
            default => throw new Exception('Unsupported service type'),
        };
    }

    private function provisionHosting(Subscription $subscription): HostingAccount
    {
        $service = $subscription->productService;

        // Create hosting account record
        $hostingAccount = new HostingAccount(
            [
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'username' => $this->generateUsername($subscription->customer),
                'domain' => $subscription->domain ?? '',
                'package' => $service->name,
                'status' => 'pending',
            ]
        );

        $hostingAccount->save();

        // Provision the account on the control panel
        try {
            $result = $this->hostingService->provisionAccount(
                $hostingAccount,
                $service
            );

            // A falsy result means the control panel rejected the request
            // without throwing — don't leave the account stuck at "pending".
            if (! $result) {
                $hostingAccount->status = 'failed';
                $hostingAccount->save();
            }

            return $hostingAccount;
        } catch (Exception $e) {
            $hostingAccount->status = 'failed';
            $hostingAccount->save();
            throw $e;
        }
    }

    private function provisionDomain(): array
    {
        // Implement domain provisioning logic
        // This would typically involve interacting with a domain registrar API
        // For now, we'll just return a success message
        return [
            'status' => 'success',
            'message' => 'Domain provisioned successfully',
        ];
    }

    private function provisionEmail(): array
    {
        // Implement email provisioning logic
        // This would typically involve creating email accounts on your mail server
        // For now, we'll just return a success message
        return [
            'status' => 'success',
            'message' => 'Email service provisioned successfully',
        ];
    }

    private function generateUsername($customer): string
    {
        // Implement logic to generate a unique username
        $baseName = strtolower(
            (string) preg_replace(
                '/[^a-z0-9]/i',
                '',
                (string) $customer->name
            )
        );
        $baseName = substr(
            $baseName,
            0,
            8
        );

        return $baseName.random_int(
            100,
            999
        );
    }

    public function manageService(Subscription $subscription, $action, array $options = [])
    {
        $service = $subscription->productService;

        return match ($service->type) {
            'hosting' => $this->manageHosting(
                $subscription,
                $action,
                $options
            ),
            'domain' => $this->manageDomain($action),
            'email' => $this->manageEmail($action),
            default => throw new Exception('Unsupported service type'),
        };
    }

    private function manageHosting(Subscription $subscription, $action, array $options = [])
    {
        $hostingAccount = HostingAccount::where(
            'subscription_id',
            $subscription->id
        )->first();

        if (! $hostingAccount) {
            throw new Exception('Hosting account not found');
        }

        switch ($action) {
            case 'suspend':
                return $this->hostingService->suspendAccount($hostingAccount);
            case 'unsuspend':
                return $this->hostingService->unsuspendAccount($hostingAccount);
            case 'terminate':
                return $this->hostingService->terminateAccount($hostingAccount);
            case 'upgrade':
                if (! isset($options['new_product'])) {
                    throw new Exception('New product required for upgrade');
                }

                return $this->hostingService->upgradeAccount(
                    $hostingAccount,
                    $options['new_product'],
                    $options
                );
            case 'downgrade':
                if (! isset($options['new_product'])) {
                    throw new Exception('New product required for downgrade');
                }

                return $this->hostingService->downgradeAccount(
                    $hostingAccount,
                    $options['new_product'],
                    $options
                );
            case 'add_addon':
                if (! isset($options['addon'])) {
                    throw new Exception('Addon name required');
                }

                return $this->hostingService->addAddon(
                    $hostingAccount,
                    $options['addon']
                );
            case 'remove_addon':
                if (! isset($options['addon'])) {
                    throw new Exception('Addon name required');
                }

                return $this->hostingService->removeAddon(
                    $hostingAccount,
                    $options['addon']
                );
            default:
                throw new Exception('Unsupported action for hosting account');
        }
    }

    private function manageDomain($action): array
    {
        // Implement domain management logic
        // This would typically involve interacting with a domain registrar API
        // For now, we'll just return a success message
        return [
            'status' => 'success',
            'message' => "Domain {$action} successfully",
        ];
    }

    private function manageEmail($action): array
    {
        // Implement email management logic
        // This would typically involve managing email accounts on your mail server
        // For now, we'll just return a success message
        return [
            'status' => 'success',
            'message' => "Email service {$action} successfully",
        ];
    }
}
