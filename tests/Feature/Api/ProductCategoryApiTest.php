<?php

namespace Tests\Feature\Api;

use App\Models\Market;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_public_can_list_active_categories(): void
    {
        ProductCategory::create(['name' => 'Active Cat', 'is_active' => true]);
        ProductCategory::create(['name' => 'Inactive Cat', 'is_active' => false]);

        $response = $this->getJson('/api/v1/product-categories');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains('Active Cat', $names);
        $this->assertNotContains('Inactive Cat', $names);
    }

    public function test_guest_cannot_access_manage_endpoint(): void
    {
        $this->getJson('/api/v1/product-categories/manage')
            ->assertUnauthorized();
    }

    public function test_commercant_cannot_manage_categories(): void
    {
        $user = User::factory()->create();
        $user->assignRole('COMMERCANT');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/product-categories/manage')->assertForbidden();
        $this->postJson('/api/v1/product-categories', ['name' => 'Test'])->assertForbidden();
    }

    public function test_super_admin_can_list_all_categories(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($user);

        ProductCategory::create(['name' => 'Cat A', 'is_active' => true]);
        ProductCategory::create(['name' => 'Cat B', 'is_active' => false]);

        $response = $this->getJson('/api/v1/product-categories/manage');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_super_admin_can_create_category(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/product-categories', [
            'name' => 'Fruits & Légumes',
            'description' => 'Produits frais',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Fruits & Légumes')
            ->assertJsonPath('data.description', 'Produits frais')
            ->assertJsonPath('data.slug', 'fruits-legumes');

        $this->assertDatabaseHas('product_categories', [
            'name' => 'Fruits & Légumes',
            'slug' => 'fruits-legumes',
        ]);
    }

    public function test_create_requires_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/product-categories', ['description' => 'Sans nom'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_super_admin_can_update_category(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($user);

        $category = ProductCategory::create(['name' => 'Ancien nom', 'description' => 'Old']);

        $response = $this->putJson("/api/v1/product-categories/{$category->id}", [
            'name' => 'Nouveau nom',
            'description' => 'Nouvelle description',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nouveau nom')
            ->assertJsonPath('data.description', 'Nouvelle description');

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Nouveau nom',
        ]);
    }

    public function test_super_admin_can_delete_unused_category(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($user);

        $category = ProductCategory::create(['name' => 'À supprimer']);

        $this->deleteJson("/api/v1/product-categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
    }

    public function test_can_delete_category_with_products_and_nullifies_category_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($user);

        $merchant = User::factory()->create();
        $market = Market::factory()->create();
        $category = ProductCategory::create(['name' => 'Utilisée']);

        $product = Product::create([
            'user_id' => $merchant->id,
            'market_id' => $market->id,
            'category_id' => $category->id,
            'name' => 'Produit test',
            'price' => 1000,
            'unit' => 'kg',
        ]);

        $this->deleteJson("/api/v1/product-categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => null,
        ]);
    }

    public function test_can_delete_category_linked_to_market(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($user);

        $market = Market::factory()->create();
        $category = ProductCategory::create(['name' => 'Liée marché']);
        $market->productCategories()->attach($category->id);

        $this->deleteJson("/api/v1/product-categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('market_product_category', [
            'market_id' => $market->id,
            'product_category_id' => $category->id,
        ]);
    }

    public function test_admin_marche_can_manage_categories(): void
    {
        $market = Market::factory()->create();
        $user = User::factory()->create(['managed_market_id' => $market->id]);
        $user->assignRole('ADMIN_MARCHE');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/product-categories', [
            'name' => 'Catégorie Admin',
            'description' => 'Créée par admin marché',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Catégorie Admin');
    }
}