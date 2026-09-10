<?php

namespace App\Http\Resources\Commerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'commerce_id' => $this->commerce_id,
            'stock_id' => $this->stock_id,
            'supplier_name' => $this->supplier_name,
            'purchase_date' => $this->purchase_date,
            'payment_status' => $this->payment_status,
            'paid_from_cash' => $this->paid_from_cash,
            'cash_register_id' => $this->cash_register_id,
            'cash_session_id' => $this->cash_session_id,
            'total_amount' => $this->total_amount,
            'user_id' => $this->user_id,
            'notes' => $this->notes,
            'stock' => $this->whenLoaded('stock', fn () => [
                'id' => $this->stock->id,
                'name' => $this->stock->name,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
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
