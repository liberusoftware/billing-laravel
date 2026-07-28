<?php

declare(strict_types=1);

namespace App\Enums;

enum ConnectorType: string
{
    case Crm = 'crm';
    case Accounting = 'accounting';
    case Erp = 'erp';
    case EventBus = 'event_bus';
}
