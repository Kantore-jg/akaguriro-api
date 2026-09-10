<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StoreStockExitRequest;
use App\Http\Resources\Commerce\StockMovementResource;
use App\Services\Commerce\StockExitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockExitController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private StockExitService $stockExitService) {}

    public function store(StoreStockExitRequest $request): JsonResponse
    {
        $movements = $this->stockExitService->create(
            $this->commerce($request),
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(
            StockMovementResource::collection(collect($movements)),
            'Sortie de stock enregistrée',
            201
        );
    }
}
