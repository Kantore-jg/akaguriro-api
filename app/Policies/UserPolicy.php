<?php

namespace App\Policies;

use App\Models\User;
use App\Services\UserService;

class UserPolicy
{
    public function __construct(private UserService $userService) {}

    public function viewAny(User $user): bool
    {
        return $user->can('manage_users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('manage_users') && $this->userService->canManage($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('manage_users') && $this->userService->canManage($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('manage_users') && $this->userService->canManage($user, $model);
    }
}