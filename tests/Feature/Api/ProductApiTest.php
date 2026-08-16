<?php

namespace Tests\Feature\Api;

use App\Models\Market;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_market_admin_cannot_create_product_for_market_outside_scope(): void
    {
        $marketA = Market::factory()->create(['province' => 'BUJUMBURA', 'city' => 'BUJUMBURA']);
        $marketB = Market::factory()->create(['province' => 'GITEGA', 'city' => 'GITEGA']);

        $admin = User::factory()->create([
            'managed_market_id' => $marketA->id,
        ]);
        $admin->assignRole('ADMIN_MARCHE');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/products', [
            'market_id' => $marketB->id,
            'name' => 'Produit interdit',
            'price' => 1200,
            'unit' => 'kg',
            'stock' => 10,
            'available' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['market_id']);
    }

    public function test_market_admin_cannot_attach_product_to_merchant_outside_scope(): void
    {
        $marketA = Market::factory()->create(['province' => 'BUJUMBURA', 'city' => 'BUJUMBURA']);
        $marketB = Market::factory()->create(['province' => 'GITEGA', 'city' => 'GITEGA']);

        $admin = User::factory()->create([
            'managed_market_id' => $marketA->id,
        ]);
        $admin->assignRole('ADMIN_MARCHE');

        $merchant = User::factory()->create([
            'managed_market_id' => $marketB->id,
        ]);
        $merchant->assignRole('COMMERCANT');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/products', [
            'market_id' => $marketA->id,
            'user_id' => $merchant->id,
            'name' => 'Produit interdit',
            'price' => 1200,
            'unit' => 'kg',
            'stock' => 10,
            'available' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }
}
