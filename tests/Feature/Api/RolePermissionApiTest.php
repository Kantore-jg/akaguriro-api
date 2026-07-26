<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_list_role_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/permissions/roles')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'roles',
                    'permissions',
                ],
            ]);
    }

    public function test_market_admin_cannot_access_role_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN_MARCHE');
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/permissions/roles')->assertForbidden();
    }

    public function test_super_admin_can_update_role_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        $role = Role::findByName('COMMERCANT', 'sanctum');

        $this->putJson("/api/v1/permissions/roles/{$role->id}", [
            'permissions' => ['manage_products', 'manage_sales'],
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'COMMERCANT')
            ->assertJsonPath('data.permissions.0', 'manage_products');
    }

    public function test_super_admin_role_is_protected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        $role = Role::findByName('SUPER_ADMIN', 'sanctum');

        $this->putJson("/api/v1/permissions/roles/{$role->id}", [
            'permissions' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }
}
