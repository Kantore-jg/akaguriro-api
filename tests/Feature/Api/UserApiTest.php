<?php

namespace Tests\Feature\Api;

use App\Models\Market;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_list_users(): void
    {
        $this->getJson('/api/v1/users')->assertUnauthorized();
    }

    public function test_commercant_cannot_manage_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole('COMMERCANT');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/users')->assertForbidden();
        $this->postJson('/api/v1/users', [
            'name' => 'Test',
            'email' => 'test@akaguriro.bi',
            'password' => 'password123',
            'role' => 'USER',
        ])->assertForbidden();
    }

    public function test_super_admin_can_list_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        User::factory()->create(['name' => 'Listed User']);

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_super_admin_can_create_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Nouvel Utilisateur',
            'email' => 'nouveau.user@akaguriro.bi',
            'phone' => '+25779111111',
            'password' => 'password123',
            'role' => 'USER',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nouvel Utilisateur')
            ->assertJsonPath('data.roles.0', 'USER');

        $this->assertDatabaseHas('users', [
            'email' => 'nouveau.user@akaguriro.bi',
        ]);
    }

    public function test_create_requires_password_and_email(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/users', [
            'name' => 'Sans email',
            'role' => 'USER',
        ])->assertUnprocessable();
    }

    public function test_super_admin_can_update_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        $user = User::factory()->create(['name' => 'Ancien nom']);
        $user->assignRole('USER');

        $this->putJson("/api/v1/users/{$user->id}", [
            'name' => 'Nouveau nom',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nouveau nom')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_super_admin_can_delete_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        $user = User::factory()->create();
        $user->assignRole('USER');

        $this->deleteJson("/api/v1/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_cannot_delete_self(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/users/{$admin->id}")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_cannot_delete_last_super_admin(): void
    {
        $keeper = User::factory()->create();
        $keeper->assignRole('SUPER_ADMIN');
        $removable = User::factory()->create();
        $removable->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($keeper);

        $this->deleteJson("/api/v1/users/{$removable->id}")
            ->assertOk();

        $this->deleteJson("/api/v1/users/{$keeper->id}")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_admin_marche_can_create_commercant(): void
    {
        $market = Market::factory()->create();
        $admin = User::factory()->create(['managed_market_id' => $market->id]);
        $admin->assignRole('ADMIN_MARCHE');
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/users', [
            'name' => 'Commerçant Local',
            'email' => 'commercant.local@akaguriro.bi',
            'password' => 'password123',
            'role' => 'COMMERCANT',
        ])
            ->assertCreated()
            ->assertJsonPath('data.roles.0', 'COMMERCANT');
    }

    public function test_create_rejects_duplicate_phone(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        User::factory()->create(['phone' => '+25779111111']);

        $this->postJson('/api/v1/users', [
            'name' => 'Doublon Tel',
            'email' => 'doublon.tel@akaguriro.bi',
            'password' => 'password123',
            'role' => 'USER',
            'phone' => '+257 79111111',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.phone.0', 'Ce numéro de téléphone est déjà utilisé par un autre compte.');
    }

    public function test_create_accepts_empty_phone(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/users', [
            'name' => 'Sans Tel',
            'email' => 'sans.tel@akaguriro.bi',
            'password' => 'password123',
            'role' => 'USER',
            'phone' => '',
        ])
            ->assertCreated()
            ->assertJsonPath('data.phone', null);
    }

    public function test_admin_marche_cannot_create_super_admin(): void
    {
        $market = Market::factory()->create();
        $admin = User::factory()->create(['managed_market_id' => $market->id]);
        $admin->assignRole('ADMIN_MARCHE');
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/users', [
            'name' => 'Fake Admin',
            'email' => 'fake.admin@akaguriro.bi',
            'password' => 'password123',
            'role' => 'SUPER_ADMIN',
        ])->assertUnprocessable();
    }
}