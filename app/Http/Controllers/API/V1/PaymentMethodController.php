<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Services\PaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function __construct(private PaymentMethodService $paymentMethodService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['market_id', 'is_active']);

        // Merchants only see active methods for their market (via query market_id or later place).
        if ($request->user()->hasRole('COMMERCANT') && ! isset($filters['is_active'])) {
            $filters['is_active'] = true;
        }

        $methods = $this->paymentMethodService->list($filters, $request->user());

        return ApiResponse::success(PaymentMethodResource::collection($methods));
    }

    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        $method = $this->paymentMethodService->create($request->validated(), $request->user());

        return ApiResponse::success(new PaymentMethodResource($method), 'Moyen de paiement créé', 201);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $method = $this->paymentMethodService->update($paymentMethod, $request->validated(), $request->user());

        return ApiResponse::success(new PaymentMethodResource($method), 'Moyen de paiement mis à jour');
    }

    public function destroy(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $method = $this->paymentMethodService->deactivate($paymentMethod, $request->user());

        return ApiResponse::success(new PaymentMethodResource($method), 'Moyen de paiement désactivé');
    }
}
