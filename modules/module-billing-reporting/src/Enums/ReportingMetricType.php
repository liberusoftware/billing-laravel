<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Enums;

enum ReportingMetricType: string
{
    case Mrr = 'mrr';
    case Arr = 'arr';
    case Churn = 'churn';
    case Aging = 'aging';
    case Revenue = 'revenue';
    case Tax = 'tax';
    case Usage = 'usage';
    case Provisioning = 'provisioning';
    case Collection = 'collection';
    case Provider = 'provider';
}
