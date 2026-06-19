<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class UserService
{
    private const MARKET_ADMIN_MANAGEABLE_ROLES = [
        UserRole::Commercant->value,
        UserRole::User->value,
    ];

    public function list(array $filters = [], int $perPage = 50, ?User $actor = null): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['roles', 'managedMarket', 'chiefPlaces.market'])
            ->orderBy('name');

        if ($actor) {
            $this->applyActorScope($query, $actor);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['role'])) {
            $query->role($filters['role']);
        }

        if (! empty($filters['market_id'])) {
            $query->where(function ($q) use ($filters) {
                $marketId = $filters['market_id'];
                $q->where('managed_market_id', $marketId)
                    ->orWhereHas('chiefPlaces', fn ($p) => $p->where('market_id', $marketId));
            });
        }

        return $query->paginate($perPage);
    }

    public function create(array $data, ?User $actor = null): User
    {
        $role = $data['role'] ?? UserRole::User->value;

        if ($actor) {
            $this->assertCanAssignRole($actor, $role);
        }

        $this->validateRoleConstraints($role, $data['managed_market_id'] ?? null);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'is_active' => $data['is_active'] ?? true,
            'managed_market_id' => $role === UserRole::AdminMarche->value
                ? ($data['managed_market_id'] ?? null)
                : null,
        ]);

        $user->syncRoles([$role]);

        return $user->fresh(['roles', 'managedMarket', 'chiefPlaces.market']);
    }

    public function update(User $user, array $data, User $actor): User
    {
        $this->assertCanManage($actor, $user);

        $role = $data['role'] ?? $user->roles->first()?->name ?? UserRole::User->value;
        $this->assertCanAssignRole($actor, $role);

        $managedMarketId = $role === UserRole::AdminMarche->value
            ? ($data['managed_market_id'] ?? $user->managed_market_id)
            : null;

        $this->validateRoleConstraints($role, $managedMarketId);

        if ($user->id === $actor->id && isset($data['is_active']) && ! $data['is_active']) {
            throw ValidationException::withMessages([
                'is_active' => ['Vous ne pouvez pas désactiver votre propre compte.'],
            ]);
        }

        if ($user->id === $actor->id && $role !== ($user->roles->first()?->name ?? UserRole::User->value)) {
            throw ValidationException::withMessages([
                'role' => ['Vous ne pouvez pas modifier votre propre rôle.'],
            ]);
        }

        $payload = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'is_active' => $data['is_active'] ?? $user->is_active,
            'managed_market_id' => $managedMarketId,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $user->syncRoles([$role]);

        return $user->fresh(['roles', 'managedMarket', 'chiefPlaces.market']);
    }

    public function delete(User $user, User $actor): void
    {
        $this->assertCanManage($actor, $user);

        if ($user->id === $actor->id) {
            throw ValidationException::withMessages([
                'user' => ['Vous ne pouvez pas supprimer votre propre compte.'],
            ]);
        }

        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            $superAdminCount = User::role(UserRole::SuperAdmin->value)->count();
            if ($superAdminCount <= 1) {
                throw ValidationException::withMessages([
                    'user' => ['Impossible de supprimer le dernier super administrateur.'],
                ]);
            }
        }

        $user->tokens()->delete();
        $user->delete();
    }

    public function canManage(User $actor, User $target): bool
    {
        if ($actor->can('manage_users') && $actor->hasRole(UserRole::SuperAdmin->value)) {
            return true;
        }

        if (! $actor->hasRole(UserRole::AdminMarche->value) || ! $actor->managed_market_id) {
            return false;
        }

        if ($target->id === $actor->id) {
            return true;
        }

        if ($target->hasRole(UserRole::SuperAdmin->value) || $target->hasRole(UserRole::AdminMarche->value)) {
            return false;
        }

        return $this->isLinkedToMarket($target, $actor->managed_market_id);
    }

    private function applyActorScope(Builder $query, User $actor): void
    {
        if ($actor->hasRole(UserRole::SuperAdmin->value)) {
            return;
        }

        if ($actor->hasRole(UserRole::AdminMarche->value) && $actor->managed_market_id) {
            $marketId = $actor->managed_market_id;
            $query->where(function ($q) use ($marketId, $actor) {
                $q->where('id', $actor->id)
                    ->orWhere(function ($q2) use ($marketId) {
                        $q2->whereHas('roles', fn ($r) => $r->whereIn('name', self::MARKET_ADMIN_MANAGEABLE_ROLES))
                            ->where(function ($q3) use ($marketId) {
                                $q3->whereHas('chiefPlaces', fn ($p) => $p->where('market_id', $marketId))
                                    ->orWhereHas('placeRequests', fn ($r) => $r->where('market_id', $marketId));
                            });
                    });
            });

            return;
        }

        $query->whereRaw('0 = 1');
    }

    private function assertCanManage(User $actor, User $target): void
    {
        if (! $this->canManage($actor, $target)) {
            throw ValidationException::withMessages([
                'user' => ['Vous n\'êtes pas autorisé à gérer cet utilisateur.'],
            ]);
        }
    }

    private function assertCanAssignRole(User $actor, string $role): void
    {
        if ($actor->hasRole(UserRole::SuperAdmin->value)) {
            return;
        }

        if ($actor->hasRole(UserRole::AdminMarche->value)) {
            if (! in_array($role, self::MARKET_ADMIN_MANAGEABLE_ROLES, true)) {
                throw ValidationException::withMessages([
                    'role' => ['Vous ne pouvez assigner que les rôles Commerçant ou Utilisateur.'],
                ]);
            }

            return;
        }

        throw ValidationException::withMessages([
            'role' => ['Vous n\'êtes pas autorisé à assigner ce rôle.'],
        ]);
    }

    private function isLinkedToMarket(User $user, int $marketId): bool
    {
        if ($user->chiefPlaces()->where('market_id', $marketId)->exists()) {
            return true;
        }

        return $user->placeRequests()->where('market_id', $marketId)->exists();
    }

    private function validateRoleConstraints(string $role, ?int $managedMarketId): void
    {
        if ($role === UserRole::AdminMarche->value && ! $managedMarketId) {
            throw ValidationException::withMessages([
                'managed_market_id' => ['Un marché doit être assigné pour un administrateur de marché.'],
            ]);
        }

        $validRoles = array_column(UserRole::cases(), 'value');
        if (! in_array($role, $validRoles, true)) {
            throw ValidationException::withMessages([
                'role' => ['Le rôle sélectionné est invalide.'],
            ]);
        }
    }
}