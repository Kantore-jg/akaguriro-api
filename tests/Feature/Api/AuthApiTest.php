<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Nouvel Utilisateur',
            'email' => 'nouveau@akaguriro.bi',
            'phone' => '+25779123456',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'nouveau@akaguriro.bi')
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', [
            'email' => 'nouveau@akaguriro.bi',
        ]);

        $user = User::where('email', 'nouveau@akaguriro.bi')->first();
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('USER'));
    }
}