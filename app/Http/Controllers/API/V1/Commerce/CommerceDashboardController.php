<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Services\Commerce\CommerceDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommerceDashboardController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private CommerceDashboardService $dashboardService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $kpis = $this->dashboardService->getKpis($this->commerce($request));

        return ApiResponse::success($kpis);
    }
}
