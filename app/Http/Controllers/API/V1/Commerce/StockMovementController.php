<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Http\Resources\Commerce\StockMovementResource;
use App\Services\Commerce\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private StockService $stockService) {}

    public function index(Request $request): JsonResponse
    {
        $movements = $this->stockService->listMovements(
            $this->commerce($request),
            $request->only(['stock_id', 'product_id', 'type', 'from', 'to'])
        );

        return ApiResponse::success(StockMovementResource::collection($movements));
    }
}
