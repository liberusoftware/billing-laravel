<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Policies;

use Liberu\Billing\Catalog\Models\Product;

final class ProductPolicy
{
    public function viewAny(object $user): bool
    {
        return $this->access($user);
    }

    public function view(object $user, Product $product): bool
    {
        return $this->access($user) && $this->teamOwns($user, $product);
    }

    public function create(object $user): bool
    {
        return $this->access($user);
    }

    private function access(object $user): bool
    {
        return ! method_exists($user, 'tokenCan') || $user->tokenCan('billing.catalog.write') || $user->tokenCan('billing.catalog.read') || $user->tokenCan('*');
    }

    private function teamOwns(object $user, Product $product): bool
    {
        $teamId = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $product->team_id === null || ($teamId !== null && (int) $teamId === (int) $product->team_id);
    }
}
