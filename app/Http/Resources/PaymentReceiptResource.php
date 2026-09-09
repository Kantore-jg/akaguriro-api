<?php

namespace App\Http\Resources;

use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $storage = app(FileStorageService::class);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'market_id' => $this->market_id,
            'place_id' => $this->place_id,
            'period_year' => $this->period_year,
            'period_month' => $this->period_month,
            'payment_method_id' => $this->payment_method_id,
            'file_url' => $storage->url($this->file_path),
            'amount' => $this->amount,
            'reference' => $this->reference,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'history' => $this->history,
            'user' => new UserResource($this->whenLoaded('user')),
            'market' => new MarketResource($this->whenLoaded('market')),
            'place' => new PlaceResource($this->whenLoaded('place')),
            'payment_method' => new PaymentMethodResource($this->whenLoaded('paymentMethod')),
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'created_at' => $this->created_at,
        ];
    }
}