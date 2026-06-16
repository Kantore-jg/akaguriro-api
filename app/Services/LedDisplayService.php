<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\LedDisplay;
use App\Models\Market;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class LedDisplayService
{
    public function __construct(private FileStorageService $fileStorage) {}

    public function getMarketDisplay(Market $market): array
    {
        return Cache::remember("led.market.{$market->id}", 30, function () use ($market) {
            $topMerchants = User::role(UserRole::Commercant->value)
                ->whereHas('products', fn ($q) => $q->where('market_id', $market->id))
                ->with(['chiefPlaces' => fn ($q) => $q->where('market_id', $market->id)])
                ->withCount(['products' => fn ($q) => $q->where('market_id', $market->id)])
                ->orderByDesc('products_count')
                ->limit(5)
                ->get()
                ->map(fn (User $user) => $this->mapMerchant($user));

            $trendingProducts = Product::query()
                ->where('market_id', $market->id)
                ->where('available', true)
                ->orderByDesc('view_count')
                ->orderByDesc('is_trending')
                ->limit(8)
                ->with(['images', 'category'])
                ->get()
                ->map(fn (Product $product) => $this->mapProduct($product));

            $announcements = Announcement::query()
                ->where('market_id', $market->id)
                ->where('is_active', true)
                ->where('show_on_led', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest()
                ->limit(10)
                ->get(['id', 'title', 'content'])
                ->map(fn (Announcement $a) => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'content' => $a->content,
                    'text' => "📢 {$a->title} : {$a->content}",
                ]);

            $stats = [
                'total_places' => $market->total_places,
                'occupied_places' => $market->occupied_places,
                'available_places' => max(0, $market->total_places - $market->occupied_places),
                'occupancy_rate' => $market->total_places > 0
                    ? round(($market->occupied_places / $market->total_places) * 100, 1)
                    : 0,
                'products_count' => $market->products()->count(),
                'merchants_count' => $topMerchants->count(),
                'visit_count' => $market->visit_count,
            ];

            $payload = [
                'market' => $market->only(['id', 'name', 'city', 'slug']),
                'top_merchants' => $topMerchants,
                'trending_products' => $trendingProducts,
                'announcements' => $announcements,
                'statistics' => $stats,
                'refreshed_at' => now()->toIso8601String(),
                'refresh_interval' => 30,
            ];

            LedDisplay::updateOrCreate(
                ['market_id' => $market->id, 'display_type' => 'main'],
                [
                    'payload' => $payload,
                    'last_refreshed_at' => now(),
                ]
            );

            return $payload;
        });
    }

    private function mapMerchant(User $user): array
    {
        $place = $user->chiefPlaces->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'image' => $this->fileStorage->url($user->avatar),
            'category' => $place?->category ?? 'Commerce Général',
            'active_place_number' => $place?->number,
            'active_market_id' => $place?->market_id,
            'rating' => 4.8,
            'products_count' => $user->products_count ?? 0,
        ];
    }

    private function mapProduct(Product $product): array
    {
        $primary = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'unit' => $product->unit,
            'category' => $product->category?->name ?? 'Général',
            'image' => $primary ? $this->fileStorage->url($primary->path) : null,
            'is_trending' => $product->is_trending,
            'view_count' => $product->view_count,
            'market_id' => $product->market_id,
        ];
    }
}