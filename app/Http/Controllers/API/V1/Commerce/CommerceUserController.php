<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StoreCommerceUserRequest;
use App\Http\Requests\Commerce\UpdateCommerceUserRequest;
use App\Http\Resources\Commerce\CommerceUserResource;
use App\Models\CommerceUser;
use App\Services\Commerce\CommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommerceUserController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private CommerceService $commerceService) {}

    public function index(Request $request): JsonResponse
    {
        $commerce = $this->commerce($request);

        $memberships = CommerceUser::query()
            ->where('commerce_id', $commerce->id)
            ->with('user')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(CommerceUserResource::collection($memberships));
    }

    public function store(StoreCommerceUserRequest $request): JsonResponse
    {
        $membership = $this->commerceService->addUser(
            $this->commerce($request),
            $request->validated()
        );

        return ApiResponse::success(new CommerceUserResource($membership), 'Membre ajouté', 201);
    }

    public function update(UpdateCommerceUserRequest $request, CommerceUser $commerceUser): JsonResponse
    {
        $commerce = $this->commerce($request);

        if ((int) $commerceUser->commerce_id !== (int) $commerce->id) {
            throw ValidationException::withMessages([
                'commerce_user' => ['Membre introuvable pour ce commerce.'],
            ]);
        }

        $membership = $this->commerceService->updateMembership($commerceUser, $request->validated());

        return ApiResponse::success(new CommerceUserResource($membership), 'Membre mis à jour');
    }
}
