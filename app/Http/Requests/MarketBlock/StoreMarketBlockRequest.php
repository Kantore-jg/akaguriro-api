<?php

namespace App\Http\Requests\MarketBlock;

use App\Models\MarketBlock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarketBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [MarketBlock::class, $this->route('market')]) ?? false;
    }

    public function rules(): array
    {
        $market = $this->route('market');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('market_blocks')->where(fn ($q) => $q->where('market_id', $market->id)),
            ],
            'code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}