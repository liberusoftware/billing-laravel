<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Enums;

enum CatalogStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
