<?php

namespace Tests\Feature\Api;

use App\Enums\PlaceStatus;
use App\Models\Market;
use App\Models\MarketBlock;
use App\Models\Place;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlaceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_market_admin_cannot_assign_chief_outside_assigned_market(): void
    {
        $marketA = Market::factory()->create();
        $marketB = Market::factory()->create();
        $block = MarketBlock::create([
            'market_id' => $marketB->id,
            'name' => 'Bloc B',
            'code' => 'B',
            'description' => 'Bloc de test',
            'total_places' => 2,
            'is_active' => true,
        ]);
        $category = ProductCategory::create([
            'name' => 'Commerce Général',
            'is_active' => true,
        ]);

        $place = Place::create([
            'market_id' => $marketB->id,
            'market_block_id' => $block->id,
            'number' => 'B-01',
            'status' => PlaceStatus::Available->value,
            'product_category_ids' => [$category->id],
            'category' => $category->name,
            'qr_code' => 'TEST-PLACE-B-01',
        ]);

        $admin = User::factory()->create(['managed_market_id' => $marketA->id]);
        $admin->assignRole('ADMIN_MARCHE');
        Sanctum::actingAs($admin);

        $target = User::factory()->create();
        $target->assignRole('COMMERCANT');
        $linkedPlace = Place::create([
            'market_id' => $marketB->id,
            'market_block_id' => $block->id,
            'number' => 'B-02',
            'status' => PlaceStatus::Occupied->value,
            'product_category_ids' => [$category->id],
            'category' => $category->name,
            'chief_user_id' => $target->id,
            'qr_code' => 'TEST-PLACE-B-02',
        ]);

        $this->postJson("/api/v1/places/{$place->id}/assign-chief", [
            'user_id' => $target->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['place_id']);
    }

    public function test_market_admin_can_assign_chief_for_commercant_created_in_same_market(): void
    {
        $market = Market::factory()->create();
        $block = MarketBlock::create([
            'market_id' => $market->id,
            'name' => 'Bloc A',
            'code' => 'A',
            'description' => 'Bloc de test',
            'total_places' => 2,
            'is_active' => true,
        ]);
        $category = ProductCategory::create([
            'name' => 'Commerce Général',
            'is_active' => true,
        ]);

        $place = Place::create([
            'market_id' => $market->id,
            'market_block_id' => $block->id,
            'number' => 'A-01',
            'status' => PlaceStatus::Available->value,
            'product_category_ids' => [$category->id],
            'category' => $category->name,
            'qr_code' => 'TEST-PLACE-A-01',
        ]);

        $admin = User::factory()->create(['managed_market_id' => $market->id]);
        $admin->assignRole('ADMIN_MARCHE');
        Sanctum::actingAs($admin);

        $merchant = User::factory()->create(['managed_market_id' => $market->id]);
        $merchant->assignRole('COMMERCANT');

        $this->postJson("/api/v1/places/{$place->id}/assign-chief", [
            'user_id' => $merchant->id,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.chief.id', $merchant->id)
            ->assertJsonPath('data.status', 'occupied');
    }
}
