<?php

namespace App\Http\Resources;

use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $storage = app(FileStorageService::class);

        return [
            'id' => $this->id,
            'path' => $storage->url($this->path),
            'is_primary' => $this->is_primary,
            'sort_order' => $this->sort_order,
        ];
    }
}