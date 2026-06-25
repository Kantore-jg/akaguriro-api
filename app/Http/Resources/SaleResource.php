<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'user_id' => $this->user_id,
            'market_id' => $this->market_id,
            'place_id' => $this->place_id,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'client_email' => $this->client_email,
            'payment_type' => $this->payment_type?->value,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'merchant' => $this->whenLoaded('merchant', fn () => [
                'id' => $this->merchant->id,
                'name' => $this->merchant->name,
                'phone' => $this->merchant->phone,
                'email' => $this->merchant->email,
            ]),
            'market' => $this->whenLoaded('market', fn () => new MarketResource($this->market)),
            'place' => $this->whenLoaded('place', fn () => new PlaceResource($this->place)),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
        ];
    }
}