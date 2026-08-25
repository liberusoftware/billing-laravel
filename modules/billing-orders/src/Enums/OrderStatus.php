<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
