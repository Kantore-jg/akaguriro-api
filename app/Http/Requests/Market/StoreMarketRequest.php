<?php

namespace App\Http\Requests\Market;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_markets') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'total_places' => ['nullable', 'integer', 'min:0'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'category_tags' => ['nullable', 'array'],
            'category_tags.*' => ['string', 'max:50'],
            'image' => ['nullable', 'image', 'max:5120'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}