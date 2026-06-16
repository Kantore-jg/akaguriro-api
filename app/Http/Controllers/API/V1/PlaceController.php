<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Place\StorePlaceRequest;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use App\Models\User;
use App\Services\PlaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    public function __construct(private PlaceService $placeService) {}

    public function index(Request $request): JsonResponse
    {
        $places = $this->placeService->list($request->only([
            'market_id', 'status', 'search',
        ]), (int) $request->get('per_page', 15));

        return ApiResponse::success(PlaceResource::collection($places));
    }

    public function show(Place $place): JsonResponse
    {
        $this->authorize('view', $place);

        return ApiResponse::success(new PlaceResource($place->load(['market', 'chief', 'members.user'])));
    }

    public function store(StorePlaceRequest $request): JsonResponse
    {
        $place = $this->placeService->create($request->validated());

        return ApiResponse::success(new PlaceResource($place), 'Place créée', 201);
    }

    public function update(Request $request, Place $place): JsonResponse
    {
        $this->authorize('update', $place);

        $data = $request->validate([
            'status' => ['sometimes', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $place = $this->placeService->update($place, $data);

        return ApiResponse::success(new PlaceResource($place), 'Place mise à jour');
    }

    public function assignChief(Request $request, Place $place): JsonResponse
    {
        $this->authorize('update', $place);

        $data = $request->validate(['user_id' => ['required', 'exists:users,id']]);
        $user = User::findOrFail($data['user_id']);
        $place = $this->placeService->assignChief($place, $user);

        return ApiResponse::success(new PlaceResource($place), 'Chef de place assigné');
    }
}