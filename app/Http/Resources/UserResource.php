<?php

namespace App\Http\Resources;

use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $storage = app(FileStorageService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $storage->url($this->avatar),
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->getAllPermissions()->pluck('name')),
            'managed_market_id' => $this->managed_market_id,
            'created_at' => $this->created_at,
        ];
    }
}