<?php

namespace Tests\Feature\Api;

use App\Models\Market;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MarketApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_can_list_markets(): void
    {
        Market::factory()->create(['name' => 'Test Market', 'city' => 'Bujumbura']);

        $response = $this->getJson('/api/v1/markets');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'message']);
    }

    public function test_super_admin_can_create_market(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/markets', [
            'name' => 'Nouveau Marché',
            'city' => 'Ngozi',
            'description' => 'Marché test',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nouveau Marché');
    }
}