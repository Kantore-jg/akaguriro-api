<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->list(
            $request->only(['search', 'role', 'is_active', 'market_id']),
            (int) $request->get('per_page', 50),
            $request->user(),
        );

        return ApiResponse::success(UserResource::collection($users));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated(), $request->user());

        return ApiResponse::success(new UserResource($user), 'Utilisateur créé', 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $updated = $this->userService->update($user, $request->validated(), $request->user());

        return ApiResponse::success(new UserResource($updated), 'Utilisateur mis à jour');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user, $request->user());

        return ApiResponse::success(null, 'Utilisateur supprimé');
    }
}