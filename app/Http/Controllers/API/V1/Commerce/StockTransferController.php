<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StoreTransferRequest;
use App\Http\Resources\Commerce\StockTransferResource;
use App\Models\StockTransfer;
use App\Services\Commerce\StockTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private StockTransferService $transferService) {}

    public function index(Request $request): JsonResponse
    {
        $transfers = $this->transferService->list(
            $this->commerce($request),
            $request->only(['from_stock_id', 'to_stock_id'])
        );

        return ApiResponse::success(StockTransferResource::collection($transfers));
    }

    public function store(StoreTransferRequest $request): JsonResponse
    {
        $transfer = $this->transferService->create(
            $this->commerce($request),
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(new StockTransferResource($transfer), 'Transfert enregistré', 201);
    }

    public function show(Request $request, StockTransfer $transfer): JsonResponse
    {
        $transfer = $this->transferService->show($this->commerce($request), $transfer);

        return ApiResponse::success(new StockTransferResource($transfer));
    }
}
