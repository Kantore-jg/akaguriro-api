<?php

namespace App\Http\Requests\Place;

use App\Enums\PlaceStatus;
use App\Models\Market;
use App\Models\MarketBlock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'market_block_id' => ['required', 'exists:market_blocks,id'],
            'number' => [
                'required', 'string', 'max:50',
                Rule::unique('places')->where(fn ($q) => $q->where('market_id', $this->market_id)),
            ],
            'product_category_ids' => ['required', 'array', 'min:1'],
            'product_category_ids.*' => ['integer', 'exists:product_categories,id'],
            'status' => ['nullable', Rule::enum(PlaceStatus::class)],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            $marketId = (int) $this->input('market_id');

            if ($user?->managed_market_id && ! $user->can('manage_markets')) {
                if ($marketId !== (int) $user->managed_market_id) {
                    $validator->errors()->add(
                        'market_id',
                        'Vous ne pouvez créer des emplacements que pour votre marché assigné.',
                    );
                }
            }

            $blockId = $this->input('market_block_id');
            if ($blockId) {
                $block = MarketBlock::find($blockId);
                if (! $block || (int) $block->market_id !== $marketId) {
                    $validator->errors()->add(
                        'market_block_id',
                        'Ce bloc n\'appartient pas au marché sélectionné.',
                    );
                }
            }

            $categoryIds = array_values(array_unique(array_map('intval', $this->input('product_category_ids', []))));
            if (empty($categoryIds)) {
                return;
            }

            $market = Market::with('productCategories')->find($marketId);
            $allowedIds = $market?->productCategories->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [];

            foreach ($categoryIds as $categoryId) {
                if (! in_array($categoryId, $allowedIds, true)) {
                    $validator->errors()->add(
                        'product_category_ids',
                        'Une ou plusieurs catégories ne sont pas autorisées pour ce marché.',
                    );
                    break;
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('product_category_ids') && is_array($this->product_category_ids)) {
            $this->merge([
                'product_category_ids' => array_values(array_unique(array_map('intval', $this->product_category_ids))),
            ]);
        }
    }
}