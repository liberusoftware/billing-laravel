<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     */
    public function run(): void
    {
        DB::table('permissions')->delete();
        foreach (self::permissionList() as $value) {
            DB::table('permissions')->insert([
                'name' => $value,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public static function permissionList(): array
    {
        return [
            'view_affiliate',
            'view_any_affiliate',
            'create_affiliate',
            'update_affiliate',
            'restore_affiliate',
            'restore_any_affiliate',
            'replicate_affiliate',
            'reorder_affiliate',
            'delete_affiliate',
            'delete_any_affiliate',
            'force_delete_affiliate',
            'force_delete_any_affiliate',
            'view_hosting::account',
            'view_any_hosting::account',
            'create_hosting::account',
            'update_hosting::account',
            'restore_hosting::account',
            'restore_any_hosting::account',
            'replicate_hosting::account',
            'reorder_hosting::account',
            'delete_hosting::account',
            'delete_any_hosting::account',
            'force_delete_hosting::account',
            'force_delete_any_hosting::account',
            'view_partial::payment',
            'view_any_partial::payment',
            'create_partial::payment',
            'update_partial::payment',
            'restore_partial::payment',
            'restore_any_partial::payment',
            'replicate_partial::payment',
            'reorder_partial::payment',
            'delete_partial::payment',
            'delete_any_partial::payment',
            'force_delete_partial::payment',
            'force_delete_any_partial::payment',
            'view_products::service',
            'view_any_products::service',
            'create_products::service',
            'update_products::service',
            'restore_products::service',
            'restore_any_products::service',
            'replicate_products::service',
            'reorder_products::service',
            'delete_products::service',
            'delete_any_products::service',
            'force_delete_products::service',
            'force_delete_any_products::service',
            'view_refund',
            'view_any_refund',
            'create_refund',
            'update_refund',
            'restore_refund',
            'restore_any_refund',
            'replicate_refund',
            'reorder_refund',
            'delete_refund',
            'delete_any_refund',
            'force_delete_refund',
            'force_delete_any_refund',
            'view_role',
            'view_any_role',
            'create_role',
            'update_role',
            'delete_role',
            'delete_any_role',
            'view:user',
            'view_any:user',
            'create:user',
            'update:user',
            'restore:user',
            'restore_any:user',
            'replicate:user',
            'reorder:user',
            'delete:user',
            'delete_any:user',
            'force_delete:user',
            'force_delete_any:user',
            'page_EditProfile',
            'page_PersonalAccessTokensPage',
            'page_UpdateProfileInformationPage',
        ];
    }
}
