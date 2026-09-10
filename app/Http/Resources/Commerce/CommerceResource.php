<?php

namespace App\Http\Resources\Commerce;

use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommerceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $storage = app(FileStorageService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'rccm' => $this->rccm,
            'nif' => $this->nif,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'province' => $this->province,
            'commune' => $this->commune,
            'zone' => $this->zone,
            'colline' => $this->colline,
            'description' => $this->description,
            'logo_url' => $storage->url($this->logo_path),
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'commerce_users_count' => $this->whenCounted('commerceUsers'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
