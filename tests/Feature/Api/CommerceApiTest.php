<?php

namespace Tests\Feature\Api;

use App\Enums\CommerceUserRole;
use App\Enums\UserRole;
use App\Models\CashRegister;
use App\Models\Commerce;
use App\Models\CommerceProduct;
use App\Models\CommerceUser;
use App\Models\Stock;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommerceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_create_commerce_and_owner(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);
        Sanctum::actingAs($admin);

        $create = $this->postJson('/api/v1/commerces', [
            'name' => 'Boutique Jean',
            'type' => 'Boutique générale',
            'phone' => '71000000',
            'province' => 'Gitega',
            'status' => 'active',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Boutique Jean');

        $commerceId = $create->json('data.id');
        $this->assertDatabaseHas('stocks', ['commerce_id' => $commerceId, 'name' => 'Stock principal']);
        $this->assertDatabaseHas('cash_registers', ['commerce_id' => $commerceId]);

        $owner = $this->postJson("/api/v1/commerces/{$commerceId}/owner", [
            'name' => 'Jean Owner',
            'email' => 'jean.owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '71111111',
        ]);

        $owner->assertCreated()
            ->assertJsonPath('data.email', 'jean.owner@example.com');

        $this->assertDatabaseHas('commerce_users', [
            'commerce_id' => $commerceId,
            'role' => 'owner',
        ]);
    }

    public function test_owner_can_purchase_open_cash_and_sell(): void
    {
        $commerce = Commerce::create([
            'name' => 'Boutique Test',
            'status' => 'active',
        ]);
        $stock = Stock::create([
            'commerce_id' => $commerce->id,
            'name' => 'Principal',
            'status' => 'active',
        ]);
        $register = CashRegister::create([
            'commerce_id' => $commerce->id,
            'name' => 'Caisse 1',
            'status' => 'active',
        ]);
        $product = CommerceProduct::create([
            'commerce_id' => $commerce->id,
            'name' => 'Sucre 1kg',
            'unit' => 'kg',
            'purchase_price' => 2500,
            'sale_price' => 3000,
            'min_stock' => 10,
            'status' => 'active',
        ]);

        $owner = User::factory()->create(['email' => 'owner@test.com']);
        $owner->assignRole(UserRole::CommerceUser->value);
        CommerceUser::create([
            'commerce_id' => $commerce->id,
            'user_id' => $owner->id,
            'role' => CommerceUserRole::Owner,
            'is_active' => true,
        ]);

        Sanctum::actingAs($owner);

        $headers = ['X-Commerce-Id' => $commerce->id];

        $open = $this->postJson('/api/v1/commerces/current/cash-sessions/open', [
            'cash_register_id' => $register->id,
            'opening_float' => 100000,
        ], $headers);
        $open->assertCreated();

        $purchase = $this->postJson('/api/v1/commerces/current/purchases', [
            'stock_id' => $stock->id,
            'supplier_name' => 'XYZ',
            'purchase_date' => now()->toDateString(),
            'payment_status' => 'paid',
            'paid_from_cash' => true,
            'cash_register_id' => $register->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 100, 'unit_cost' => 2500],
            ],
        ], $headers);
        $purchase->assertCreated();
        $this->assertEquals(250000, (float) $purchase->json('data.total_amount'));

        $this->assertDatabaseHas('stock_items', [
            'stock_id' => $stock->id,
            'product_id' => $product->id,
        ]);
        $this->assertEquals(100, (float) \App\Models\StockItem::where('stock_id', $stock->id)->value('quantity'));

        $sale = $this->postJson('/api/v1/commerces/current/sales', [
            'stock_id' => $stock->id,
            'cash_register_id' => $register->id,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 3000],
            ],
        ], $headers);
        $sale->assertCreated();
        $this->assertEquals(15000, (float) $sale->json('data.total_amount'));

        $this->assertEquals(95, (float) \App\Models\StockItem::where('stock_id', $stock->id)->value('quantity'));

        $dashboard = $this->getJson('/api/v1/commerces/current/dashboard', $headers);
        $dashboard->assertOk()
            ->assertJsonPath('data.sales_count', 1);
    }
}
