<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_sales');
    }

    public function view(User $user, Sale $sale): bool
    {
        if (! $user->can('manage_sales')) {
            return false;
        }

        if ($user->id === $sale->user_id) {
            return true;
        }

        if ($user->can('manage_merchants') || $user->can('manage_markets')) {
            if ($user->managed_market_id) {
                return $sale->market_id === $user->managed_market_id;
            }

            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_sales');
    }
}