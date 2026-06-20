<?php

namespace App\Http\Resources;

use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $storage = app(FileStorageService::class);
        $chiefPlace = $this->chiefPlaces->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'image' => $storage->url($this->avatar) ?? $this->avatar,
            'category' => $chiefPlace?->category ?? 'Commerce Général',
            'active_place_id' => $chiefPlace?->id,
            'active_place_number' => $chiefPlace?->number,
            'active_market_id' => $chiefPlace?->market_id,
            'joined_date' => $this->created_at?->toDateString(),
            'verified' => (bool) $this->email_verified_at,
            'bio' => null,
            'products_count' => $this->whenCounted('products'),
        ];
    }
}