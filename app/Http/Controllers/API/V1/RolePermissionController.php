<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    private const ROLE_LABELS = [
        'SUPER_ADMIN' => 'Super Administrateur',
        'ADMIN_MARCHE' => 'Admin Marché',
        'COMMERCANT' => 'Commerçant',
        'USER' => 'Utilisateur',
    ];

    private const PERMISSION_LABELS = [
        'manage_users' => 'Gérer les utilisateurs',
        'manage_markets' => 'Gérer les marchés',
        'manage_places' => 'Gérer les emplacements',
        'manage_merchants' => 'Gérer les commerçants',
        'manage_products' => 'Gérer les produits',
        'manage_categories' => 'Gérer les catégories',
        'manage_receipts' => 'Gérer les reçus',
        'manage_sales' => 'Gérer les ventes',
        'manage_statistics' => 'Voir les statistiques',
        'manage_announcements' => 'Gérer les annonces',
        'manage_led' => 'Gérer l\'affichage LED',
    ];

    private const PERMISSION_GROUPS = [
        'manage_users' => 'Administration',
        'manage_markets' => 'Administration',
        'manage_places' => 'Marchés',
        'manage_merchants' => 'Marchés',
        'manage_products' => 'Commerce',
        'manage_categories' => 'Commerce',
        'manage_receipts' => 'Ventes',
        'manage_sales' => 'Ventes',
        'manage_statistics' => 'Tableaux de bord',
        'manage_announcements' => 'Communication',
        'manage_led' => 'Communication',
    ];

    public function index(): JsonResponse
    {
        $roles = Role::query()
            ->where('guard_name', 'sanctum')
            ->with('permissions:id,name,guard_name')
            ->orderByRaw("CASE name
                WHEN 'SUPER_ADMIN' THEN 0
                WHEN 'ADMIN_MARCHE' THEN 1
                WHEN 'COMMERCANT' THEN 2
                WHEN 'USER' THEN 3
                ELSE 4
            END")
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => self::ROLE_LABELS[$role->name] ?? $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
                'editable' => $role->name !== UserRole::SuperAdmin->value,
            ]);

        $permissions = Permission::query()
            ->where('guard_name', 'sanctum')
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => self::PERMISSION_LABELS[$permission->name] ?? $permission->name,
                'group' => self::PERMISSION_GROUPS[$permission->name] ?? 'Autres',
            ]);

        return ApiResponse::success([
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $this->assertEditableRole($role);

        $data = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'sanctum'),
            ],
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role->syncPermissions($data['permissions']);
        $role->load('permissions:id,name,guard_name');

        return ApiResponse::success([
            'id' => $role->id,
            'name' => $role->name,
            'label' => self::ROLE_LABELS[$role->name] ?? $role->name,
            'permissions' => $role->permissions->pluck('name')->values(),
            'editable' => $role->name !== UserRole::SuperAdmin->value,
        ], 'Permissions du rôle mises à jour');
    }

    private function assertEditableRole(Role $role): void
    {
        if ($role->name === UserRole::SuperAdmin->value) {
            throw ValidationException::withMessages([
                'role' => ['Le rôle Super Administrateur est protégé.'],
            ]);
        }
    }
}
