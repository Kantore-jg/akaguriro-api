<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage_users',
            'manage_markets',
            'manage_places',
            'manage_merchants',
            'manage_products',
            'manage_categories',
            'manage_receipts',
            'manage_sales',
            'manage_statistics',
            'manage_announcements',
            'manage_led',
            'view_market_ops',
            'manage_commerces',
            'commerce_manage_users',
            'commerce_manage_products',
            'commerce_manage_stocks',
            'commerce_manage_sales',
            'commerce_manage_cash',
            'commerce_view_reports',
            'commerce_manage_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        $commercePermissions = [
            'commerce_manage_users',
            'commerce_manage_products',
            'commerce_manage_stocks',
            'commerce_manage_sales',
            'commerce_manage_cash',
            'commerce_view_reports',
            'commerce_manage_settings',
        ];

        $roles = [
            UserRole::SuperAdmin->value => $permissions,
            UserRole::AdminMarche->value => [
                'manage_users', 'manage_places', 'manage_merchants', 'manage_products', 'manage_categories',
                'manage_receipts', 'manage_sales', 'manage_statistics', 'manage_announcements', 'manage_led',
                'view_market_ops',
            ],
            UserRole::ProprietaireMarche->value => [
                'manage_statistics',
                'view_market_ops',
            ],
            UserRole::Commercant->value => ['manage_products', 'manage_sales'],
            UserRole::CommerceUser->value => $commercePermissions,
            UserRole::User->value => [],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
