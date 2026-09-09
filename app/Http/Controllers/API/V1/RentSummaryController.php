<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\RentSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentSummaryController extends Controller
{
    public function __construct(private RentSummaryService $rentSummaryService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->can('manage_receipts') || $user->can('view_market_ops'),
            403
        );

        $summary = $this->rentSummaryService->summarize(
            $request->only(['year', 'month', 'market_id', 'payment_method_id']),
            $user,
        );

        return ApiResponse::success($summary);
    }
}
