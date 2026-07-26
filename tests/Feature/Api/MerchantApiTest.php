<?php

namespace Tests\Feature\Api;

use App\Models\Market;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_merchants_index_includes_market_commercant_without_chief_place(): void
    {
        $marketA = Market::factory()->create(['name' => 'Marché A']);
        $marketB = Market::factory()->create(['name' => 'Marché B']);

        $merchantA = User::factory()->create([
            'name' => 'Commerçant Sans Étal',
            'managed_market_id' => $marketA->id,
        ]);
        $merchantA->assignRole('COMMERCANT');

        $merchantB = User::factory()->create([
            'name' => 'Autre Commerçant',
            'managed_market_id' => $marketB->id,
        ]);
        $merchantB->assignRole('COMMERCANT');

        $this->getJson('/api/v1/merchants?market_id='.$marketA->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $merchantA->id,
                'active_market_id' => $marketA->id,
            ])
            ->assertJsonMissing([
                'id' => $merchantB->id,
            ]);
    }
}
