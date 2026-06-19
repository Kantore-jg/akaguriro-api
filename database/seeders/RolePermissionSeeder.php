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
            'manage_receipts',
            'manage_statistics',
            'manage_announcements',
            'manage_led',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        $roles = [
            UserRole::SuperAdmin->value => $permissions,
            UserRole::AdminMarche->value => [
                'manage_users', 'manage_places', 'manage_merchants', 'manage_products',
                'manage_receipts', 'manage_statistics', 'manage_announcements', 'manage_led',
            ],
            UserRole::Commercant->value => ['manage_products'],
            UserRole::User->value => [],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
            $role->syncPermissions($rolePermissions);
        }
    }
}