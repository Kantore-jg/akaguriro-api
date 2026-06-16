<?php

namespace App\Policies;

use App\Models\Place;
use App\Models\User;

class PlacePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Place $place): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_places');
    }

    public function update(User $user, Place $place): bool
    {
        if ($user->can('manage_places')) {
            return true;
        }

        return $user->managed_market_id === $place->market_id;
    }

    public function delete(User $user, Place $place): bool
    {
        return $user->can('manage_places');
    }
}