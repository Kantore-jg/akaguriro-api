<?php

namespace Tests\Feature\Api;

use App\Models\Market;
use App\Models\Place;
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

    public function test_super_admin_can_update_market(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');
        $market = Market::factory()->create([
            'name' => 'Marché Initial',
            'city' => 'Bujumbura',
            'total_places' => 40,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/markets/{$market->id}", [
            'name' => 'Marché Mis à Jour',
            'city' => 'Gitega',
            'total_places' => 60,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Marché Mis à Jour')
            ->assertJsonPath('data.city', 'Gitega')
            ->assertJsonPath('data.total_places', 60);

        $this->assertDatabaseHas('markets', [
            'id' => $market->id,
            'name' => 'Marché Mis à Jour',
            'city' => 'Gitega',
            'total_places' => 60,
        ]);
    }

    public function test_super_admin_can_delete_empty_market(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');
        $market = Market::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/markets/{$market->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('markets', ['id' => $market->id]);
    }

    public function test_cannot_delete_market_with_places(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');
        $market = Market::factory()->create();

        Place::create([
            'market_id' => $market->id,
            'number' => 'A-01',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/markets/{$market->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('markets', ['id' => $market->id, 'deleted_at' => null]);
    }

    public function test_market_admin_cannot_create_market(): void
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN_MARCHE');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/markets', [
            'name' => 'Marché Interdit',
            'city' => 'Ngozi',
        ]);

        $response->assertForbidden();
    }
}