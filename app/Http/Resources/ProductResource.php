<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'unit' => $this->unit,
            'stock' => $this->stock,
            'available' => $this->available,
            'is_trending' => $this->is_trending,
            'view_count' => $this->view_count,
            'merchant' => new UserResource($this->whenLoaded('merchant')),
            'market' => new MarketResource($this->whenLoaded('market')),
            'place' => new PlaceResource($this->whenLoaded('place')),
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at,
        ];
    }
}