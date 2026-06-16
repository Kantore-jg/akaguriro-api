<?php

namespace App\Policies;

use App\Models\PlaceRequest;
use App\Models\User;

class PlaceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_places') || $user->can('manage_merchants');
    }

    public function approve(User $user, PlaceRequest $placeRequest): bool
    {
        if ($user->can('manage_places')) {
            return true;
        }

        return $user->managed_market_id === $placeRequest->market_id;
    }

    public function reject(User $user, PlaceRequest $placeRequest): bool
    {
        return $this->approve($user, $placeRequest);
    }
}