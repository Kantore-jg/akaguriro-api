<?php

namespace App\Policies;

use App\Models\Market;
use App\Models\MarketBlock;
use App\Models\User;

class MarketBlockPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, MarketBlock $block): bool
    {
        return true;
    }

    public function create(User $user, Market $market): bool
    {
        return $this->canManageMarket($user, $market->id);
    }

    public function update(User $user, MarketBlock $block): bool
    {
        return $this->canManageMarket($user, $block->market_id);
    }

    public function delete(User $user, MarketBlock $block): bool
    {
        return $this->canManageMarket($user, $block->market_id);
    }

    private function canManageMarket(User $user, int $marketId): bool
    {
        if (! $user->can('manage_places')) {
            return false;
        }

        if ($user->managed_market_id) {
            return (int) $user->managed_market_id === $marketId;
        }

        return true;
    }
}