<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StoreCommerceRequest;
use App\Http\Requests\Commerce\StoreOwnerRequest;
use App\Http\Requests\Commerce\UpdateCommerceRequest;
use App\Http\Resources\Commerce\CommerceResource;
use App\Http\Resources\UserResource;
use App\Models\Commerce;
use App\Services\Commerce\CommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommerceController extends Controller
{
    public function __construct(private CommerceService $commerceService) {}

    public function index(Request $request): JsonResponse
    {
        $commerces = $this->commerceService->list($request->only(['status', 'search']));

        return ApiResponse::success(CommerceResource::collection($commerces));
    }

    public function store(StoreCommerceRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo');
        }

        $commerce = $this->commerceService->create($data);

        return ApiResponse::success(new CommerceResource($commerce), 'Commerce créé', 201);
    }

    public function show(Commerce $commerce): JsonResponse
    {
        $commerce->loadCount('commerceUsers');

        return ApiResponse::success(new CommerceResource($commerce));
    }

    public function update(UpdateCommerceRequest $request, Commerce $commerce): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo');
        }

        $commerce = $this->commerceService->update($commerce, $data);

        return ApiResponse::success(new CommerceResource($commerce), 'Commerce mis à jour');
    }

    public function createOwner(StoreOwnerRequest $request, Commerce $commerce): JsonResponse
    {
        $user = $this->commerceService->createOwner($commerce, $request->validated());

        return ApiResponse::success(new UserResource($user), 'Compte principal créé', 201);
    }
}
