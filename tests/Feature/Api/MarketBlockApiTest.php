<?php

namespace Tests\Feature\Api;

use App\Models\Market;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MarketBlockApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_create_block_with_rent_amount(): void
    {
        $market = Market::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/markets/{$market->id}/blocks", [
            'name' => 'Bloc A',
            'code' => 'A',
            'description' => 'Bloc test',
            'rent_amount' => 25000,
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Bloc A')
            ->assertJsonPath('data.rent_amount', 25000);

        $this->assertDatabaseHas('market_blocks', [
            'market_id' => $market->id,
            'name' => 'Bloc A',
            'rent_amount' => 25000,
        ]);
    }
}
