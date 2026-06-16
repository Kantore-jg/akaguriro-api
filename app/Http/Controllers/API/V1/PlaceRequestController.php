<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceRequest\StorePlaceRequestRequest;
use App\Http\Resources\PlaceRequestResource;
use App\Models\PlaceRequest;
use App\Services\PlaceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaceRequestController extends Controller
{
    public function __construct(private PlaceRequestService $placeRequestService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PlaceRequest::class);

        $filters = $request->only(['status', 'market_id', 'user_id']);
        if ($request->user()->managed_market_id && ! $request->user()->can('manage_markets')) {
            $filters['market_id'] = $request->user()->managed_market_id;
        }

        $requests = $this->placeRequestService->list($filters, (int) $request->get('per_page', 50));

        return ApiResponse::success(PlaceRequestResource::collection($requests));
    }

    public function mine(Request $request): JsonResponse
    {
        $requests = $this->placeRequestService->list([
            'user_id' => $request->user()->id,
        ], (int) $request->get('per_page', 50));

        return ApiResponse::success(PlaceRequestResource::collection($requests));
    }

    public function store(StorePlaceRequestRequest $request): JsonResponse
    {
        $placeRequest = $this->placeRequestService->create(
            $request->user(),
            $request->validated(),
        );

        return ApiResponse::success(new PlaceRequestResource($placeRequest), 'Demande créée', 201);
    }

    public function approve(Request $request, PlaceRequest $placeRequest): JsonResponse
    {
        $this->authorize('approve', $placeRequest);

        $data = $request->validate(['place_id' => ['nullable', 'exists:places,id']]);

        $placeRequest = $this->placeRequestService->approve(
            $placeRequest,
            $request->user(),
            $data['place_id'] ?? null,
        );

        return ApiResponse::success(new PlaceRequestResource($placeRequest), 'Demande approuvée');
    }

    public function reject(Request $request, PlaceRequest $placeRequest): JsonResponse
    {
        $this->authorize('reject', $placeRequest);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $placeRequest = $this->placeRequestService->reject(
            $placeRequest,
            $request->user(),
            $data['reason'],
        );

        return ApiResponse::success(new PlaceRequestResource($placeRequest), 'Demande rejetée');
    }
}