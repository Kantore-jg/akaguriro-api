<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SaleService $saleService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('manage_merchants') && ! $user->can('manage_markets')) {
            abort(403, 'Accès réservé aux administrateurs.');
        }

        $this->authorize('viewAny', Sale::class);

        $filters = $request->only(['user_id', 'market_id', 'payment_type', 'from', 'to', 'search']);

        if ($request->user()->managed_market_id && ! $request->user()->can('manage_markets')) {
            $filters['market_id'] = $request->user()->managed_market_id;
        }

        $sales = $this->saleService->list($filters, (int) $request->get('per_page', 50));

        return ApiResponse::success(SaleResource::collection($sales));
    }

    public function mine(Request $request): JsonResponse
    {
        $sales = $this->saleService->list([
            'user_id' => $request->user()->id,
            'from' => $request->get('from'),
            'to' => $request->get('to'),
            'payment_type' => $request->get('payment_type'),
            'search' => $request->get('search'),
        ], (int) $request->get('per_page', 50));

        return ApiResponse::success(SaleResource::collection($sales));
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->create($request->user(), $request->validated());

        return ApiResponse::success(new SaleResource($sale), 'Vente enregistrée', 201);
    }

    public function show(Request $request, Sale $sale): JsonResponse
    {
        $this->authorize('view', $sale);

        return ApiResponse::success(new SaleResource(
            $sale->load(['merchant', 'market', 'place', 'items.product'])
        ));
    }
}