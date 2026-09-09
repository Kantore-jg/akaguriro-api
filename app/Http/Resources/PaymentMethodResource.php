<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'market_id' => $this->market_id,
            'name' => $this->name,
            'type' => $this->type,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'is_active' => $this->is_active,
            'market' => new MarketResource($this->whenLoaded('market')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
