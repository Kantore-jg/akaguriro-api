<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Market\StoreMarketRequest;
use App\Http\Requests\Market\UpdateMarketRequest;
use App\Http\Resources\MarketResource;
use App\Models\Market;
use App\Services\MarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function __construct(private MarketService $marketService) {}

    public function index(Request $request): JsonResponse
    {
        $markets = $this->marketService->list($request->only([
            'search', 'city', 'is_active', 'sort', 'direction',
        ]), (int) $request->get('per_page', 15));

        return ApiResponse::success(MarketResource::collection($markets));
    }

    public function popular(): JsonResponse
    {
        $markets = $this->marketService->getPopular();

        return ApiResponse::success(MarketResource::collection($markets));
    }

    public function show(Request $request, Market $market): JsonResponse
    {
        $this->authorize('view', $market);

        $this->marketService->recordVisit($market, $request->user()?->id, $request->ip());

        return ApiResponse::success(new MarketResource($market->load(['blocks'])));
    }

    public function statistics(Market $market): JsonResponse
    {
        $this->authorize('view', $market);

        return ApiResponse::success($this->marketService->getStatistics($market));
    }

    public function store(StoreMarketRequest $request): JsonResponse
    {
        $market = $this->marketService->create(
            $request->validated(),
            $request->file('image'),
            $request->file('cover_image'),
        );

        return ApiResponse::success(new MarketResource($market), 'Marché créé', 201);
    }

    public function update(UpdateMarketRequest $request, Market $market): JsonResponse
    {
        $market = $this->marketService->update(
            $market,
            $request->validated(),
            $request->file('image'),
            $request->file('cover_image'),
        );

        return ApiResponse::success(new MarketResource($market), 'Marché mis à jour');
    }

    public function destroy(Market $market): JsonResponse
    {
        $this->authorize('delete', $market);
        $this->marketService->delete($market);

        return ApiResponse::success(null, 'Marché supprimé');
    }
}