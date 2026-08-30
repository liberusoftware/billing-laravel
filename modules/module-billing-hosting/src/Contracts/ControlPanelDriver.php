<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Contracts;

interface ControlPanelDriver extends HostingDriver
{
    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function unsuspend(array $attributes): array;

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function changePackage(array $attributes): array;

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function addAddon(array $attributes): array;

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    public function removeAddon(array $attributes): array;
}
