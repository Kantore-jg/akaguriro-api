<?php

namespace App\Http\Resources\Commerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'commerce_id' => $this->commerce_id,
            'stock_id' => $this->stock_id,
            'cash_session_id' => $this->cash_session_id,
            'payment_method' => $this->payment_method,
            'total_amount' => $this->total_amount,
            'total_cost' => $this->total_cost,
            'user_id' => $this->user_id,
            'sold_at' => $this->sold_at,
            'notes' => $this->notes,
            'stock' => $this->whenLoaded('stock', fn () => [
                'id' => $this->stock->id,
                'name' => $this->stock->name,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'unit_cost' => $item->unit_cost,
                'line_total' => $item->line_total,
                'product' => $item->relationLoaded('product') ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                ] : null,
            ])),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
