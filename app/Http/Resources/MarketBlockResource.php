<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'market_id' => $this->market_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'total_places' => $this->total_places,
            'is_active' => $this->is_active,
        ];
    }
}