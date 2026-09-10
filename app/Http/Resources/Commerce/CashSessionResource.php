<?php

namespace App\Http\Resources\Commerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cash_register_id' => $this->cash_register_id,
            'opened_by' => $this->opened_by,
            'closed_by' => $this->closed_by,
            'opening_float' => $this->opening_float,
            'closing_counted' => $this->closing_counted,
            'theoretical_balance' => $this->theoretical_balance,
            'variance' => $this->variance,
            'status' => $this->status,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'notes' => $this->notes,
            'register' => $this->whenLoaded('register', fn () => new CashRegisterResource($this->register)),
            'movements' => $this->whenLoaded('movements', fn () => $this->movements->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'amount' => $m->amount,
                'motif' => $m->motif,
                'moved_at' => $m->moved_at,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
