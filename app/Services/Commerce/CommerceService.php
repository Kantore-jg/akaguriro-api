<?php

namespace App\Services\Commerce;

use App\Enums\CommerceStatus;
use App\Enums\CommerceUserRole;
use App\Enums\UserRole;
use App\Models\CashRegister;
use App\Models\Commerce;
use App\Models\CommerceUser;
use App\Models\Stock;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CommerceService
{
    public function __construct(private FileStorageService $storage) {}

    public function list(array $filters = []): Collection
    {
        $query = Commerce::query()->withCount('commerceUsers')->orderBy('name');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nif', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function create(array $data): Commerce
    {
        if (! empty($data['logo'])) {
            $data['logo_path'] = $this->storage->store($data['logo'], 'commerces');
            unset($data['logo']);
        }

        $commerce = Commerce::create([
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'rccm' => $data['rccm'] ?? null,
            'nif' => $data['nif'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'province' => $data['province'] ?? null,
            'commune' => $data['commune'] ?? null,
            'zone' => $data['zone'] ?? null,
            'colline' => $data['colline'] ?? null,
            'description' => $data['description'] ?? null,
            'logo_path' => $data['logo_path'] ?? null,
            'status' => $data['status'] ?? CommerceStatus::Active->value,
        ]);

        Stock::create([
            'commerce_id' => $commerce->id,
            'name' => 'Stock principal',
            'location' => $commerce->address,
            'status' => 'active',
        ]);

        CashRegister::create([
            'commerce_id' => $commerce->id,
            'name' => 'Caisse principale',
            'status' => 'active',
        ]);

        return $commerce->fresh()->loadCount('commerceUsers');
    }

    public function update(Commerce $commerce, array $data): Commerce
    {
        if (! empty($data['logo'])) {
            $data['logo_path'] = $this->storage->store($data['logo'], 'commerces');
            unset($data['logo']);
        }

        $commerce->update(collect($data)->only([
            'name', 'type', 'rccm', 'nif', 'phone', 'email', 'address',
            'province', 'commune', 'zone', 'colline', 'description', 'logo_path', 'status',
        ])->filter(fn ($v) => $v !== null)->all());

        return $commerce->fresh()->loadCount('commerceUsers');
    }

    public function createOwner(Commerce $commerce, array $data): User
    {
        if ($commerce->commerceUsers()->where('role', CommerceUserRole::Owner->value)->exists()) {
            throw ValidationException::withMessages([
                'owner' => ['Ce commerce a déjà un compte principal.'],
            ]);
        }

        return DB::transaction(function () use ($commerce, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);
            $user->syncRoles([UserRole::CommerceUser->value]);

            CommerceUser::create([
                'commerce_id' => $commerce->id,
                'user_id' => $user->id,
                'role' => CommerceUserRole::Owner,
                'is_active' => true,
            ]);

            return $user->load('roles', 'commerceMemberships.commerce');
        });
    }

    public function addUser(Commerce $commerce, array $data): CommerceUser
    {
        return DB::transaction(function () use ($commerce, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);
            $user->syncRoles([UserRole::CommerceUser->value]);

            return CommerceUser::create([
                'commerce_id' => $commerce->id,
                'user_id' => $user->id,
                'role' => $data['role'] ?? CommerceUserRole::Seller->value,
                'is_active' => $data['is_active'] ?? true,
            ])->load('user');
        });
    }

    public function updateMembership(CommerceUser $membership, array $data): CommerceUser
    {
        $membership->update(collect($data)->only(['role', 'is_active'])->all());

        if (isset($data['name']) || isset($data['phone']) || isset($data['password'])) {
            $userData = collect($data)->only(['name', 'phone'])->filter()->all();
            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }
            $membership->user->update($userData);
        }

        return $membership->fresh('user');
    }
}
