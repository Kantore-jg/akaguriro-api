<?php

namespace Database\Seeders;

use App\Enums\PlaceStatus;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Market;
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
                'city' => 'Bujumbura',
                'location' => 'Quartier Jabe, Avenue du Peuple Murundi',
                'description' => 'Le plus grand centre commercial populaire de Bujumbura.',
                'total_places' => 120,
                'occupied_places' => 0,
                'category_tags' => ['Poissons', 'Vivres', 'Textiles'],
                'image' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=600',
                'cover_image' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200',
            ],
            [
                'name' => 'Marché Central de Gitega',
                'city' => 'Gitega',
                'location' => 'Centre-ville, Boulevard de la Nation',
                'description' => 'Marché réputé pour ses légumes de montagne et son café.',
                'total_places' => 80,
                'occupied_places' => 0,
                'category_tags' => ['Céréales', 'Fruits', 'Café'],
                'image' => 'https://images.unsplash.com/photo-1506484381205-f7945653044d?w=600',
                'cover_image' => 'https://images.unsplash.com/photo-1506484381205-f7945653044d?w=1200',
            ],
        ];

        $categories = ['Poissons', 'Café & Thé', 'Fruits & Légumes', 'Textiles'];

        foreach ($categories as $catName) {
            ProductCategory::firstOrCreate(['name' => $catName], ['is_active' => true]);
        }

        foreach ($markets as $marketData) {
            $market = Market::firstOrCreate(['name' => $marketData['name']], $marketData);

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

            for ($i = 1; $i <= 5; $i++) {
                Place::firstOrCreate(
                    ['market_id' => $market->id, 'number' => 'A-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)],
                    ['status' => PlaceStatus::Available, 'category' => 'Commerce Général']
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
                    'show_on_led' => true,
                    'is_active' => true,
                    'expires_at' => now()->addMonths(3),
                    'created_by' => $superAdmin->id,
                ]
            );
        }
    }
}