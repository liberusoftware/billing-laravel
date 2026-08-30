<?php

declare(strict_types=1);

namespace Liberu\Billing\Virtualmin;

final class VirtualminProDriver extends VirtualminDriver
{
    public function key(): string
    {
        return 'virtualmin-pro';
    }
}
