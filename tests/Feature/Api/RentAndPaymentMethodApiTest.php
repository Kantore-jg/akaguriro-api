<?php

namespace Tests\Feature\Api;

use App\Enums\PlaceStatus;
use App\Enums\UserRole;
use App\Models\Market;
use App\Models\MarketBlock;
use App\Models\PaymentMethod;
use App\Models\Place;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RentAndPaymentMethodApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_admin_can_create_payment_method_and_view_rent_summary(): void
    {
        $market = Market::factory()->create();
        $admin = User::factory()->create(['managed_market_id' => $market->id]);
        $admin->assignRole(UserRole::AdminMarche->value);

        $block = MarketBlock::create([
            'market_id' => $market->id,
            'name' => 'Bloc A',
            'rent_amount' => 35000,
            'total_places' => 1,
            'is_active' => true,
        ]);

        $merchant = User::factory()->create();
        $merchant->assignRole(UserRole::Commercant->value);

        Place::create([
            'market_id' => $market->id,
            'market_block_id' => $block->id,
            'number' => 'A-01',
            'status' => PlaceStatus::Occupied,
            'chief_user_id' => $merchant->id,
        ]);

        Sanctum::actingAs($admin);

        $methodResponse = $this->postJson('/api/v1/payment-methods', [
            'name' => 'BRB',
            'type' => 'bank',
            'account_number' => '123',
            'is_active' => true,
        ]);

        $methodResponse->assertCreated()
            ->assertJsonPath('data.name', 'BRB')
            ->assertJsonPath('data.market_id', $market->id);

        $summary = $this->getJson('/api/v1/rent-summary?year='.now()->year.'&month='.now()->month);
        $summary->assertOk()
            ->assertJsonPath('data.totals.expected_amount', 35000)
            ->assertJsonPath('data.totals.occupied_places', 1);
    }

    public function test_merchant_submits_rent_with_payment_method(): void
    {
        $market = Market::factory()->create();
        $block = MarketBlock::create([
            'market_id' => $market->id,
            'name' => 'Bloc A',
            'rent_amount' => 20000,
            'total_places' => 1,
            'is_active' => true,
        ]);
        $merchant = User::factory()->create();
        $merchant->assignRole(UserRole::Commercant->value);
        $place = Place::create([
            'market_id' => $market->id,
            'market_block_id' => $block->id,
            'number' => 'A-01',
            'status' => PlaceStatus::Occupied,
            'chief_user_id' => $merchant->id,
        ]);
        $method = PaymentMethod::create([
            'market_id' => $market->id,
            'name' => 'Lumicash',
            'type' => 'mobile_money',
            'is_active' => true,
        ]);

        Sanctum::actingAs($merchant);

        $response = $this->post('/api/v1/receipts', [
            'place_id' => $place->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'payment_method_id' => $method->id,
            'file' => UploadedFile::fake()->image('quittance.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount', '20000.00')
            ->assertJsonPath('data.payment_method_id', $method->id)
            ->assertJsonPath('data.place_id', $place->id);
    }

    public function test_vacant_place_is_excluded_from_expected_rent(): void
    {
        $market = Market::factory()->create();
        $admin = User::factory()->create(['managed_market_id' => $market->id]);
        $admin->assignRole(UserRole::AdminMarche->value);
        $block = MarketBlock::create([
            'market_id' => $market->id,
            'name' => 'Bloc A',
            'rent_amount' => 35000,
            'total_places' => 1,
            'is_active' => true,
        ]);
        Place::create([
            'market_id' => $market->id,
            'market_block_id' => $block->id,
            'number' => 'A-02',
            'status' => PlaceStatus::Available,
        ]);

        Sanctum::actingAs($admin);
        $summary = $this->getJson('/api/v1/rent-summary?year='.now()->year.'&month='.now()->month);
        $summary->assertOk()
            ->assertJsonPath('data.totals.expected_amount', 0)
            ->assertJsonPath('data.totals.occupied_places', 0);
    }
}
