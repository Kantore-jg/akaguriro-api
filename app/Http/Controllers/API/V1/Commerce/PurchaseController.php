<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StorePurchaseRequest;
use App\Http\Resources\Commerce\PurchaseResource;
use App\Models\CommercePurchase;
use App\Services\Commerce\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private PurchaseService $purchaseService) {}

    public function index(Request $request): JsonResponse
    {
        $purchases = $this->purchaseService->list(
            $this->commerce($request),
            $request->only(['stock_id', 'from', 'to'])
        );

        return ApiResponse::success(PurchaseResource::collection($purchases));
    }

    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $purchase = $this->purchaseService->create(
            $this->commerce($request),
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(new PurchaseResource($purchase), 'Achat enregistré', 201);
    }

    public function show(Request $request, CommercePurchase $purchase): JsonResponse
    {
        $purchase = $this->purchaseService->show($this->commerce($request), $purchase);

        return ApiResponse::success(new PurchaseResource($purchase));
    }
}
