<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
