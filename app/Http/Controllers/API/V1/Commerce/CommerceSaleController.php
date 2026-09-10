<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StoreCommerceSaleRequest;
use App\Http\Resources\Commerce\SaleResource;
use App\Models\CommerceSale;
use App\Services\Commerce\CommerceSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommerceSaleController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private CommerceSaleService $saleService) {}

    public function index(Request $request): JsonResponse
    {
        $sales = $this->saleService->list(
            $this->commerce($request),
            $request->only(['stock_id', 'from', 'to'])
        );

        return ApiResponse::success(SaleResource::collection($sales));
    }

    public function store(StoreCommerceSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->create(
            $this->commerce($request),
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(new SaleResource($sale), 'Vente enregistrée', 201);
    }

    public function show(Request $request, CommerceSale $sale): JsonResponse
    {
        $sale = $this->saleService->show($this->commerce($request), $sale);

        return ApiResponse::success(new SaleResource($sale));
    }
}
