<?php

declare(strict_types=1);

namespace App\Enums;

enum OrganisationType: string
{
    case Company = 'company';
    case Reseller = 'reseller';
    case Partner = 'partner';
    case WhiteLabel = 'white_label';
    case Subsidiary = 'subsidiary';
    case Franchise = 'franchise';
}
