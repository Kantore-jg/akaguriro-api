<?php

namespace App\Http\Requests\MarketBlock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMarketBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('marketBlock')) ?? false;
    }

    public function rules(): array
    {
        $block = $this->route('marketBlock');

        return [
            'name' => [
                'sometimes', 'string', 'max:100',
                Rule::unique('market_blocks')
                    ->where(fn ($q) => $q->where('market_id', $block->market_id))
                    ->ignore($block->id),
            ],
            'code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}