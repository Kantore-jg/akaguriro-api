<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\MarketBlock\StoreMarketBlockRequest;
use App\Http\Requests\MarketBlock\UpdateMarketBlockRequest;
use App\Http\Resources\MarketBlockResource;
use App\Models\Market;
use App\Models\MarketBlock;
use App\Services\MarketBlockService;
use Illuminate\Http\JsonResponse;

class MarketBlockController extends Controller
{
    public function __construct(private MarketBlockService $blockService) {}

    public function index(Market $market): JsonResponse
    {
        $blocks = $this->blockService->listByMarket($market);

        return ApiResponse::success(MarketBlockResource::collection($blocks));
    }

    public function store(StoreMarketBlockRequest $request, Market $market): JsonResponse
    {
        $block = $this->blockService->create($market, $request->validated());

        return ApiResponse::success(new MarketBlockResource($block), 'Bloc créé', 201);
    }

    public function update(UpdateMarketBlockRequest $request, MarketBlock $marketBlock): JsonResponse
    {
        $block = $this->blockService->update($marketBlock, $request->validated());

        return ApiResponse::success(new MarketBlockResource($block), 'Bloc mis à jour');
    }

    public function destroy(MarketBlock $marketBlock): JsonResponse
    {
        $this->authorize('delete', $marketBlock);
        $this->blockService->delete($marketBlock);

        return ApiResponse::success(null, 'Bloc supprimé');
    }
}