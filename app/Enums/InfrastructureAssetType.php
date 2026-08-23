<?php

declare(strict_types=1);

namespace App\Enums;

enum InfrastructureAssetType: string
{
    case PhysicalServer = 'physical_server';
    case VpsHost = 'vps_host';
    case Hypervisor = 'hypervisor';
    case Switch = 'switch';
    case Router = 'router';
    case Firewall = 'firewall';
}
