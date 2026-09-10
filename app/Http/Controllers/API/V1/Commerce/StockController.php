<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StoreStockRequest;
use App\Http\Resources\Commerce\StockResource;
use App\Models\Stock;
use App\Services\Commerce\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private StockService $stockService) {}

    public function index(Request $request): JsonResponse
    {
        $stocks = $this->stockService->list($this->commerce($request));

        return ApiResponse::success(StockResource::collection($stocks));
    }

    public function store(StoreStockRequest $request): JsonResponse
    {
        $stock = $this->stockService->create($this->commerce($request), $request->validated());

        return ApiResponse::success(new StockResource($stock), 'Stock créé', 201);
    }

    public function show(Request $request, Stock $stock): JsonResponse
    {
        $this->stockService->assertBelongsToCommerce($this->commerce($request), $stock);
        $stock->load(['manager', 'items.product']);

        return ApiResponse::success(new StockResource($stock));
    }

    public function update(StoreStockRequest $request, Stock $stock): JsonResponse
    {
        $this->stockService->assertBelongsToCommerce($this->commerce($request), $stock);

        $stock = $this->stockService->update($stock, $request->validated());

        return ApiResponse::success(new StockResource($stock), 'Stock mis à jour');
    }
}
