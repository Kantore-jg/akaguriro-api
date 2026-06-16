<?php

namespace App\Http\Requests\PlaceRequest;

use Illuminate\Foundation\Http\FormRequest;

class StorePlaceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'market_id' => ['required', 'exists:markets,id'],
            'merchant_name' => ['required', 'string', 'max:255'],
            'merchant_phone' => ['required', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}