<?php

namespace App\Http\Requests\Place;

use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignChiefRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_places') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $actor = $this->user();
            $place = $this->route('place');

            if (! $actor || ! $place instanceof Place) {
                return;
            }

            if ($actor->managed_market_id && ! $actor->can('manage_markets')) {
                if ((int) $place->market_id !== (int) $actor->managed_market_id) {
                    $validator->errors()->add(
                        'place_id',
                        'Vous ne pouvez assigner un chef que sur votre marché assigné.',
                    );
                }
            }

            $targetUser = User::with(['chiefPlaces', 'placeRequests', 'managedMarket'])->find($this->input('user_id'));
            if (! $targetUser || ! $actor->managed_market_id || $actor->can('manage_markets')) {
                return;
            }

            $marketId = (int) $actor->managed_market_id;
            $linkedToMarket = $targetUser->chiefPlaces->contains(
                fn (Place $chiefPlace) => (int) $chiefPlace->market_id === $marketId
            ) || $targetUser->placeRequests->contains(
                fn ($request) => (int) $request->market_id === $marketId
            ) || (int) $targetUser->managed_market_id === $marketId;

            if (! $linkedToMarket) {
                $validator->errors()->add(
                    'user_id',
                    'Le commerçant sélectionné n\'appartient pas à votre marché.',
                );
            }
        });
    }
}
