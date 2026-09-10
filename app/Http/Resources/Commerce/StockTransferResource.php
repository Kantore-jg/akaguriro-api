<?php

namespace App\Http\Resources\Commerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'commerce_id' => $this->commerce_id,
            'transfer_no' => $this->transfer_no,
            'from_stock_id' => $this->from_stock_id,
            'to_stock_id' => $this->to_stock_id,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'notes' => $this->notes,
            'transferred_at' => $this->transferred_at,
            'from_stock' => $this->whenLoaded('fromStock', fn () => [
                'id' => $this->fromStock->id,
                'name' => $this->fromStock->name,
            ]),
            'to_stock' => $this->whenLoaded('toStock', fn () => [
                'id' => $this->toStock->id,
                'name' => $this->toStock->name,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
                'product' => $item->relationLoaded('product') ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                ] : null,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
