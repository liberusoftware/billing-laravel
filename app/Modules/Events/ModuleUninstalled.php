<?php

declare(strict_types=1);

namespace App\Modules\Events;

use App\Modules\Contracts\ModuleInterface;

readonly class ModuleUninstalled
{
    public function __construct(
        public string $name,
        public ModuleInterface $module,
    ) {}
}
