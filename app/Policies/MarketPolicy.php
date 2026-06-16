<?php

namespace App\Policies;

use App\Models\Market;
use App\Models\User;

class MarketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Market $market): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_markets');
    }

    public function update(User $user, Market $market): bool
    {
        if ($user->can('manage_markets')) {
            return true;
        }

        return $user->managed_market_id === $market->id && $user->can('manage_places');
    }

    public function delete(User $user, Market $market): bool
    {
        return $user->can('manage_markets');
    }
}