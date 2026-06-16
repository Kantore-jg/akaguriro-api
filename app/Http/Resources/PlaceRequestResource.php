<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'market_id' => $this->market_id,
            'place_id' => $this->place_id,
            'merchant_name' => $this->merchant_name,
            'merchant_phone' => $this->merchant_phone,
            'category' => $this->category,
            'description' => $this->description,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'history' => $this->history,
            'user' => new UserResource($this->whenLoaded('user')),
            'market' => new MarketResource($this->whenLoaded('market')),
            'place' => new PlaceResource($this->whenLoaded('place')),
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'created_at' => $this->created_at,
        ];
    }
}