<?php

namespace App\Services\Commerce;

use App\Enums\CommerceUserRole;
use App\Enums\UserRole;
use App\Models\Commerce;
use App\Models\CommerceUser;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CommerceAccessService
{
    public function resolveCommerce(User $user, ?int $commerceId = null): Commerce
    {
        if ($user->can('manage_commerces') && $commerceId) {
            $commerce = Commerce::find($commerceId);
            if (! $commerce) {
                throw ValidationException::withMessages([
                    'commerce_id' => ['Commerce introuvable.'],
                ]);
            }

            return $commerce;
        }

        $query = CommerceUser::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with('commerce');

        if ($commerceId) {
            $membership = $query->where('commerce_id', $commerceId)->first();
        } else {
            $membership = $query->first();
        }

        if (! $membership?->commerce) {
            throw ValidationException::withMessages([
                'commerce_id' => ['Aucun commerce accessible.'],
            ]);
        }

        return $membership->commerce;
    }

    public function membership(User $user, Commerce $commerce): ?CommerceUser
    {
        return CommerceUser::query()
            ->where('user_id', $user->id)
            ->where('commerce_id', $commerce->id)
            ->where('is_active', true)
            ->first();
    }

    public function assertPermission(User $user, Commerce $commerce, string $permission): void
    {
        if ($user->can('manage_commerces')) {
            return;
        }

        $membership = $this->membership($user, $commerce);
        if (! $membership || ! $membership->hasPermission($permission)) {
            throw ValidationException::withMessages([
                'permission' => ['Action non autorisée pour ce commerce.'],
            ]);
        }
    }

    public function assertMember(User $user, Commerce $commerce): CommerceUser
    {
        if ($user->can('manage_commerces')) {
            return new CommerceUser([
                'commerce_id' => $commerce->id,
                'user_id' => $user->id,
                'role' => CommerceUserRole::Owner,
                'is_active' => true,
            ]);
        }

        $membership = $this->membership($user, $commerce);
        if (! $membership) {
            throw ValidationException::withMessages([
                'commerce_id' => ['Vous n\'appartenez pas à ce commerce.'],
            ]);
        }

        return $membership;
    }

    public function isCommerceUser(User $user): bool
    {
        return $user->hasRole(UserRole::CommerceUser->value);
    }
}
