<?php

namespace App\Http\Requests\Sale;

use App\Enums\PaymentType;
use App\Models\Place;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_sales') ?? false;
    }

    public function rules(): array
    {
        return [
            'market_id' => ['required', 'exists:markets,id'],
            'place_id' => ['nullable', 'exists:places,id'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:30'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'payment_type' => ['required', Rule::enum(PaymentType::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            $marketId = (int) $this->input('market_id');

            if (! $user) {
                return;
            }

            if (! $user->can('manage_markets')) {
                $hasMarketAccess = $user->chiefPlaces()->where('market_id', $marketId)->exists();
                if (! $hasMarketAccess) {
                    $validator->errors()->add(
                        'market_id',
                        'Vous ne pouvez enregistrer une vente que pour votre marché assigné.',
                    );
                }
            }

            $placeId = $this->input('place_id');
            if (! $placeId) {
                return;
            }

            $place = Place::find($placeId);
            if (! $place || (int) $place->market_id !== $marketId) {
                $validator->errors()->add(
                    'place_id',
                    'L étal sélectionné n appartient pas au marché choisi.',
                );
                return;
            }

            if (! $user->can('manage_markets') && (int) $place->chief_user_id !== (int) $user->id) {
                $validator->errors()->add(
                    'place_id',
                    'Vous ne pouvez vendre que depuis votre propre étal.',
                );
            }
        });
    }
}
