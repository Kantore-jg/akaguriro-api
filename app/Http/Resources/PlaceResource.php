<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'market_id' => $this->market_id,
            'market_block_id' => $this->market_block_id,
            'number' => $this->number,
            'qr_code' => $this->qr_code,
            'status' => $this->status,
            'category' => $this->category,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'chief' => new UserResource($this->whenLoaded('chief')),
            'members' => PlaceMemberResource::collection($this->whenLoaded('members')),
            'market' => new MarketResource($this->whenLoaded('market')),
            'block' => new MarketBlockResource($this->whenLoaded('block')),
            'created_at' => $this->created_at,
        ];
    }
}