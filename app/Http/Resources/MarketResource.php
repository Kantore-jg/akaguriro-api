<?php

namespace App\Http\Resources;

use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketResource extends JsonResource
{
    private function resolveMediaUrl(?string $path, FileStorageService $storage): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : $storage->url($path);
    }

    public function toArray(Request $request): array
    {
        $storage = app(FileStorageService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'city' => $this->city,
            'location' => $this->location,
            'description' => $this->description,
            'image' => $this->resolveMediaUrl($this->image, $storage),
            'cover_image' => $this->resolveMediaUrl($this->cover_image, $storage),
            'total_places' => $this->total_places,
            'occupied_places' => $this->occupied_places,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'category_tags' => $this->category_tags ?? [],
            'is_active' => $this->is_active,
            'visit_count' => $this->visit_count,
            'places_count' => $this->whenCounted('places'),
            'products_count' => $this->whenCounted('products'),
            'blocks' => MarketBlockResource::collection($this->whenLoaded('blocks')),
            'created_at' => $this->created_at,
        ];
    }
}