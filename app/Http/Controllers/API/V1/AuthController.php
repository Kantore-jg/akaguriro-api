<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private FileStorageService $fileStorage,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['avatar']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->fileStorage->store($request->file('avatar'), 'avatars');
        }

        $result = $this->authService->register($data);

        return ApiResponse::success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Inscription réussie', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return ApiResponse::success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Connexion réussie');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, 'Déconnexion réussie');
    }

    public function profile(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()->load('roles', 'permissions')));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['avatar']);

        if ($request->hasFile('avatar')) {
            $this->fileStorage->delete($request->user()->avatar);
            $data['avatar'] = $this->fileStorage->store($request->file('avatar'), 'avatars');
        }

        $user = $this->authService->updateProfile($request->user(), $data);

        return ApiResponse::success(new UserResource($user), 'Profil mis à jour');
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->authService->updatePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('password'),
        );

        return ApiResponse::success(null, 'Mot de passe mis à jour');
    }
}