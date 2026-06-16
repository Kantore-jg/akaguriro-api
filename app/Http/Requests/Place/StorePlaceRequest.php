<?php

namespace App\Http\Requests\Place;

use App\Enums\PlaceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_places') ?? false;
    }

    public function rules(): array
    {
        return [
            'market_id' => ['required', 'exists:markets,id'],
            'market_block_id' => ['nullable', 'exists:market_blocks,id'],
            'number' => [
                'required', 'string', 'max:50',
                Rule::unique('places')->where(fn ($q) => $q->where('market_id', $this->market_id)),
            ],
            'status' => ['nullable', Rule::enum(PlaceStatus::class)],
            'category' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}