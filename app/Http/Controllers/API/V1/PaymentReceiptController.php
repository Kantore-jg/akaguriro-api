<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Receipt\StoreReceiptRequest;
use App\Http\Resources\PaymentReceiptResource;
use App\Models\PaymentReceipt;
use App\Services\PaymentReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentReceiptController extends Controller
{
    public function __construct(private PaymentReceiptService $receiptService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentReceipt::class);

        $filters = $request->only(['status', 'user_id', 'market_id']);
        if ($request->user()->managed_market_id && ! $request->user()->can('manage_markets')) {
            $filters['market_id'] = $request->user()->managed_market_id;
        }

        $receipts = $this->receiptService->list($filters, (int) $request->get('per_page', 50));

        return ApiResponse::success(PaymentReceiptResource::collection($receipts));
    }

    public function mine(Request $request): JsonResponse
    {
        $receipts = $this->receiptService->list([
            'user_id' => $request->user()->id,
        ], (int) $request->get('per_page', 50));

        return ApiResponse::success(PaymentReceiptResource::collection($receipts));
    }

    public function store(StoreReceiptRequest $request): JsonResponse
    {
        $receipt = $this->receiptService->create(
            $request->user(),
            $request->validated(),
            $request->file('file'),
        );

        return ApiResponse::success(new PaymentReceiptResource($receipt), 'Reçu soumis', 201);
    }

    public function approve(Request $request, PaymentReceipt $receipt): JsonResponse
    {
        $this->authorize('approve', $receipt);
        $receipt = $this->receiptService->approve($receipt, $request->user());

        return ApiResponse::success(new PaymentReceiptResource($receipt), 'Reçu validé');
    }

    public function reject(Request $request, PaymentReceipt $receipt): JsonResponse
    {
        $this->authorize('reject', $receipt);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $receipt = $this->receiptService->reject($receipt, $request->user(), $data['reason']);

        return ApiResponse::success(new PaymentReceiptResource($receipt), 'Reçu rejeté');
    }
}