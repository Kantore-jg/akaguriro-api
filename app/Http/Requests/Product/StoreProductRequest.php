<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_products') ?? false;
    }

    public function rules(): array
    {
        return [
            'market_id' => ['required', 'exists:markets,id'],
            'place_id' => ['nullable', 'exists:places,id'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'available' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
        ];
    }
}