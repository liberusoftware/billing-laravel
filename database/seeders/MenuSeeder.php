<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'name' => 'Home',
                'url' => '/',
                'order' => 1,
            ],
            [
                'name' => 'Dashboard',
                'url' => '/dashboard',
                'order' => 2,
            ],
            [
                'name' => 'Invoices',
                'url' => '/invoices',
                'order' => 3,
                'children' => [
                    ['name' => 'All Invoices', 'url' => '/invoices/all', 'order' => 1],
                    ['name' => 'Create Invoice', 'url' => '/invoices/create', 'order' => 2],
                    ['name' => 'Recurring Invoices', 'url' => '/invoices/recurring', 'order' => 3],
                ],
            ],
            [
                'name' => 'Payments',
                'url' => '/payments',
                'order' => 4,
                'children' => [
                    ['name' => 'Payment History', 'url' => '/payments/history', 'order' => 1],
                    ['name' => 'Make Payment', 'url' => '/payments/make', 'order' => 2],
                ],
            ],
            [
                'name' => 'Clients',
                'url' => '/clients',
                'order' => 5,
            ],
            [
                'name' => 'Reports',
                'url' => '/reports',
                'order' => 6,
            ],
            [
                'name' => 'Affiliates',
                'url' => '/affiliates',
                'order' => 7,
            ],
            [
                'name' => 'About',
                'url' => '/about',
                'order' => 8,
            ],
            [
                'name' => 'Contact',
                'url' => '/contact',
                'order' => 9,
            ],
        ];

        foreach ($menus as $menuData) {
            $this->createMenu($menuData);
        }
    }

    private function createMenu(array $menuData, $parentId = null): void
    {
        $children = $menuData['children'] ?? [];
        unset($menuData['children']);

        $menuData['parent_id'] = $parentId;
        $menu = Menu::create($menuData);

        foreach ($children as $childData) {
            $this->createMenu($childData, $menu->id);
        }
    }
}
