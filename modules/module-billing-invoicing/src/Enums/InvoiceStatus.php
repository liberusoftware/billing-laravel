<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Void = 'void';
}
