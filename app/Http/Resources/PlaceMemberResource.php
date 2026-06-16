<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'place_id' => $this->place_id,
            'user_id' => $this->user_id,
            'role' => $this->role,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}