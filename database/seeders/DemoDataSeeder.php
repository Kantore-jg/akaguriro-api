<?php

namespace Database\Seeders;

use App\Enums\PlaceStatus;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Market;
use App\Models\PaymentMethod;
use App\Models\Place;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@akaguriro.bi'],
            [
                'name' => 'Super Admin AKAGURIRO',
                'phone' => '+25770000001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $markets = [
            [
                'name' => 'Marché Siyoni de Bujumbura',
                'city' => 'BUJUMBURA',
                'province' => 'BUJUMBURA',
                'commune' => 'Mukaza',
                'zone' => 'Rohero',
                'colline' => 'Jabe',
                'location' => 'Quartier Jabe, Avenue du Peuple Murundi',
                'description' => 'Le plus grand centre commercial populaire de Bujumbura.',
                'total_places' => 120,
                'occupied_places' => 0,
                'tag_names' => ['Poissons', 'Vivres', 'Textiles'],
                'image' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=600',
                'cover_image' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200',
            ],
            [
                'name' => 'Marché Central de Gitega',
                'city' => 'GITEGA',
                'province' => 'GITEGA',
                'commune' => 'Gitega',
                'zone' => 'Nyamugari',
                'colline' => 'Centre-ville',
                'location' => 'Centre-ville, Boulevard de la Nation',
                'description' => 'Marché réputé pour ses légumes de montagne et son café.',
                'total_places' => 80,
                'occupied_places' => 0,
                'tag_names' => ['Céréales', 'Fruits', 'Café'],
                'image' => 'https://images.unsplash.com/photo-1506484381205-f7945653044d?w=600',
                'cover_image' => 'https://images.unsplash.com/photo-1506484381205-f7945653044d?w=1200',
            ],
        ];

        $categories = [
            'Poissons', 'Vivres', 'Textiles', 'Café & Thé', 'Fruits & Légumes',
            'Céréales', 'Fruits', 'Café',
        ];

        foreach ($categories as $catName) {
            ProductCategory::firstOrCreate(['name' => $catName], ['is_active' => true]);
        }

        foreach ($markets as $marketData) {
            $tagNames = $marketData['tag_names'] ?? [];
            unset($marketData['tag_names']);

            $market = Market::firstOrCreate(['name' => $marketData['name']], $marketData);

            if ($tagNames) {
                $categoryIds = ProductCategory::query()
                    ->whereIn('name', $tagNames)
                    ->pluck('id')
                    ->all();
                $market->productCategories()->sync($categoryIds);
            }

            $admin = User::firstOrCreate(
                ['email' => 'admin.'.strtolower($market->city).'@akaguriro.bi'],
                [
                    'name' => 'Admin '.$market->city,
                    'phone' => '+25771000'.str_pad((string) $market->id, 3, '0', STR_PAD_LEFT),
                    'password' => Hash::make('password'),
                    'managed_market_id' => $market->id,
                    'email_verified_at' => now(),
                ]
            );
            $admin->assignRole(UserRole::AdminMarche->value);

            $owner = User::firstOrCreate(
                ['email' => 'proprietaire.'.strtolower($market->city).'@akaguriro.bi'],
                [
                    'name' => 'Propriétaire '.$market->city,
                    'phone' => '+25772000'.str_pad((string) $market->id, 3, '0', STR_PAD_LEFT),
                    'password' => Hash::make('password'),
                    'managed_market_id' => $market->id,
                    'email_verified_at' => now(),
                ]
            );
            $owner->assignRole(UserRole::ProprietaireMarche->value);

            PaymentMethod::firstOrCreate(
                ['market_id' => $market->id, 'name' => 'Banque de la République du Burundi'],
                [
                    'type' => 'bank',
                    'account_number' => 'BRB-'.str_pad((string) $market->id, 4, '0', STR_PAD_LEFT),
                    'account_name' => 'Marché '.$market->name,
                    'is_active' => true,
                ]
            );
            PaymentMethod::firstOrCreate(
                ['market_id' => $market->id, 'name' => 'Lumicash'],
                [
                    'type' => 'mobile_money',
                    'account_number' => '79'.str_pad((string) $market->id, 6, '0', STR_PAD_LEFT),
                    'account_name' => 'Caisse '.$market->city,
                    'is_active' => true,
                ]
            );

            $marketCategoryIds = $market->productCategories()->pluck('product_categories.id')->all();
            $marketCategoryNames = $market->productCategories()->pluck('name')->all();

            for ($i = 1; $i <= 5; $i++) {
                Place::firstOrCreate(
                    ['market_id' => $market->id, 'number' => 'A-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)],
                    [
                        'status' => PlaceStatus::Available,
                        'product_category_ids' => $marketCategoryIds ?: null,
                        'category' => $marketCategoryNames ? implode(', ', $marketCategoryNames) : null,
                    ]
                );
            }
        }

        $merchant = User::firstOrCreate(
            ['email' => 'commercant@akaguriro.bi'],
            [
                'name' => 'Anésie Ndayishimiye',
                'phone' => '+25779384102',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $merchant->assignRole(UserRole::Commercant->value);

        $market = Market::first();
        $place = Place::where('market_id', $market->id)->first();
        $category = ProductCategory::first();

        if ($market && $place && $category) {
            $place->update([
                'status' => PlaceStatus::Occupied,
                'chief_user_id' => $merchant->id,
            ]);
            $market->update(['occupied_places' => 1]);

            Product::firstOrCreate(
                ['market_id' => $market->id, 'name' => 'Mukeke du Lac Tanganyika (Frais)'],
                [
                    'user_id' => $merchant->id,
                    'place_id' => $place->id,
                    'category_id' => $category->id,
                    'description' => 'Poisson frais du Lac Tanganyika.',
                    'price' => 25000,
                    'unit' => 'kg',
                    'stock' => 45,
                    'available' => true,
                    'is_trending' => true,
                ]
            );

            Announcement::firstOrCreate(
                ['market_id' => $market->id, 'title' => 'Tarifs plafonnés Mukeke'],
                [
                    'content' => 'Les tarifs du Mukeke du Lac Tanganyika sont plafonnés à 25 000 BIF maximum cette semaine.',
                    'is_active' => true,
                    'expires_at' => now()->addMonths(3),
                    'created_by' => $superAdmin->id,
                ]
            );
        }

        $this->seedIndependentCommerce();
    }

    private function seedIndependentCommerce(): void
    {
        $commerce = \App\Models\Commerce::firstOrCreate(
            ['name' => 'Boutique ABC'],
            [
                'type' => 'Boutique générale',
                'rccm' => 'RC-GITEGA-2024-001',
                'nif' => '4001234567',
                'phone' => '+25779000100',
                'email' => 'contact@boutique-abc.bi',
                'address' => 'Avenue de la Paix',
                'province' => 'GITEGA',
                'commune' => 'Gitega',
                'zone' => 'Nyamugari',
                'colline' => 'Centre-ville',
                'description' => 'Commerce indépendant de démonstration.',
                'status' => 'active',
            ]
        );

        $stock = \App\Models\Stock::firstOrCreate(
            ['commerce_id' => $commerce->id, 'name' => 'Stock principal'],
            ['location' => $commerce->address, 'status' => 'active']
        );

        \App\Models\Stock::firstOrCreate(
            ['commerce_id' => $commerce->id, 'name' => 'Dépôt'],
            ['location' => 'Entrepôt arrière', 'status' => 'active']
        );

        \App\Models\CashRegister::firstOrCreate(
            ['commerce_id' => $commerce->id, 'name' => 'Caisse principale'],
            ['status' => 'active']
        );

        $owner = User::firstOrCreate(
            ['email' => 'owner.abc@akaguriro.bi'],
            [
                'name' => 'Jean Propriétaire',
                'phone' => '+25779000101',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $owner->syncRoles([UserRole::CommerceUser->value]);

        \App\Models\CommerceUser::firstOrCreate(
            ['commerce_id' => $commerce->id, 'user_id' => $owner->id],
            ['role' => 'owner', 'is_active' => true]
        );

        $cashier = User::firstOrCreate(
            ['email' => 'caissier.abc@akaguriro.bi'],
            [
                'name' => 'Marie Caissière',
                'phone' => '+25779000102',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $cashier->syncRoles([UserRole::CommerceUser->value]);

        \App\Models\CommerceUser::firstOrCreate(
            ['commerce_id' => $commerce->id, 'user_id' => $cashier->id],
            ['role' => 'cashier', 'is_active' => true]
        );

        $stockManager = User::firstOrCreate(
            ['email' => 'stock.abc@akaguriro.bi'],
            [
                'name' => 'Paul Magasinier',
                'phone' => '+25779000103',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $stockManager->syncRoles([UserRole::CommerceUser->value]);

        \App\Models\CommerceUser::firstOrCreate(
            ['commerce_id' => $commerce->id, 'user_id' => $stockManager->id],
            ['role' => 'stock_manager', 'is_active' => true]
        );

        $products = [
            ['name' => 'Sucre 1kg', 'sku' => 'SUC-1KG', 'unit' => 'kg', 'purchase_price' => 2500, 'sale_price' => 3000, 'min_stock' => 20],
            ['name' => 'Huile 1L', 'sku' => 'HUI-1L', 'unit' => 'L', 'purchase_price' => 6000, 'sale_price' => 7000, 'min_stock' => 10],
            ['name' => 'Riz 5kg', 'sku' => 'RIZ-5KG', 'unit' => 'sac', 'purchase_price' => 12000, 'sale_price' => 15000, 'min_stock' => 5],
        ];

        foreach ($products as $productData) {
            $product = \App\Models\CommerceProduct::firstOrCreate(
                ['commerce_id' => $commerce->id, 'sku' => $productData['sku']],
                array_merge($productData, [
                    'commerce_id' => $commerce->id,
                    'status' => 'active',
                    'vat_rate' => 0,
                ])
            );

            \App\Models\StockItem::firstOrCreate(
                ['stock_id' => $stock->id, 'product_id' => $product->id],
                [
                    'quantity' => match ($productData['sku']) {
                        'SUC-1KG' => 120,
                        'HUI-1L' => 50,
                        'RIZ-5KG' => 30,
                        default => 0,
                    },
                    'avg_purchase_price' => $productData['purchase_price'],
                ]
            );
        }
    }
}
