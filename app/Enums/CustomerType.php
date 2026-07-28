<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomerType: string
{
    case Individual = 'individual';
    case Business = 'business';
    case Reseller = 'reseller';
    case WholesalePartner = 'wholesale_partner';
    case GovernmentOrganisation = 'government_organisation';
}
