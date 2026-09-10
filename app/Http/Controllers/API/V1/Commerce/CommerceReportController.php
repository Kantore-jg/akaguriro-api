<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Services\Commerce\CommerceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommerceReportController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private CommerceReportService $reportService) {}

    public function show(Request $request, string $type): JsonResponse
    {
        $report = $this->reportService->report(
            $this->commerce($request),
            $type,
            $request->only(['from', 'to'])
        );

        return ApiResponse::success($report);
    }
}
