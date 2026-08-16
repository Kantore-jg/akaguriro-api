<?php

namespace App\Http\Requests\Product;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'available' => ['nullable', 'boolean'],
            'is_trending' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $actor = $this->user();

            if (! $actor) {
                return;
            }

            $marketId = (int) $this->input('market_id');
            $actor->loadMissing('chiefPlaces');
            $allowedMarketIds = collect();

            if ($actor->managed_market_id) {
                $allowedMarketIds->push((int) $actor->managed_market_id);
            }

            $allowedMarketIds = $allowedMarketIds->merge(
                $actor->chiefPlaces->pluck('market_id')->map(fn ($id) => (int) $id),
            )->unique();

            if (! $actor->can('manage_markets')) {
                if ($allowedMarketIds->isEmpty() || ! $allowedMarketIds->contains($marketId)) {
                    $validator->errors()->add(
                        'market_id',
                        'Vous ne pouvez créer un produit que pour votre marché assigné.',
                    );
                }
            }

            if (! $this->filled('user_id') || $actor->can('manage_markets')) {
                return;
            }

            $targetUser = User::with(['chiefPlaces', 'managedMarket'])->find($this->input('user_id'));
            if (! $targetUser) {
                return;
            }

            if ((int) $targetUser->id === (int) $actor->id) {
                return;
            }

            $targetUser->loadMissing('chiefPlaces');
            $sameMarket = $allowedMarketIds->contains((int) $targetUser->managed_market_id)
                || $targetUser->chiefPlaces->contains(
                    fn ($place) => $allowedMarketIds->contains((int) $place->market_id),
                );

            if (! $sameMarket) {
                $validator->errors()->add(
                    'user_id',
                    'Le produit doit être rattaché à un commerçant du marché assigné.',
                );
            }
        });
    }
}
