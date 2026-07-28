<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\User;

enum TokenAbility: string
{
    case InvoicesRead = 'invoices:read';
    case InvoicesWrite = 'invoices:write';
    case SubscriptionsRead = 'subscriptions:read';
    case SubscriptionsWrite = 'subscriptions:write';
    case CustomersRead = 'customers:read';
    case CustomersWrite = 'customers:write';
    case QuotesRead = 'quotes:read';
    case QuotesWrite = 'quotes:write';
    case WebhooksManage = 'webhooks:manage';
    case CannedResponsesRead = 'canned-responses:read';
    case CannedResponsesWrite = 'canned-responses:write';
    case ClientNotesRead = 'client-notes:read';
    case ClientNotesWrite = 'client-notes:write';
    case PackageGroupsRead = 'package-groups:read';
    case PackageGroupsWrite = 'package-groups:write';
    case IspServicesRead = 'isp-services:read';
    case IspServicesWrite = 'isp-services:write';
    case VoipRead = 'voip:read';
    case VoipWrite = 'voip:write';
    case GitRead = 'git:read';
    case GitWrite = 'git:write';
    case InfrastructureRead = 'infrastructure:read';
    case InfrastructureWrite = 'infrastructure:write';
    case OrganisationsRead = 'organisations:read';
    case OrganisationsWrite = 'organisations:write';
    case ResellersRead = 'resellers:read';
    case ResellersWrite = 'resellers:write';
    case IntegrationsRead = 'integrations:read';
    case IntegrationsWrite = 'integrations:write';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $ability): string => $ability->value, self::cases());
    }

    /**
     * Abilities a user is permitted to request on a token.
     *
     * note: starting policy — operators get everything, everyone else is
     * read-only. Tune this mapping as finer-grained API access is needed.
     *
     * @return list<string>
     */
    public static function allowedFor(User $user): array
    {
        if ($user->hasRole(['super_admin', 'admin', 'staff'])) {
            return self::values();
        }

        return array_values(array_filter(
            self::values(),
            fn (string $ability): bool => str_ends_with($ability, ':read'),
        ));
    }
}
