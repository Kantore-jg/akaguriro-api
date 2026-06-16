<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Services\LedDisplayService;
use Illuminate\Http\JsonResponse;

class LedDisplayController extends Controller
{
    public function __construct(private LedDisplayService $ledDisplayService) {}

    public function show(Market $market): JsonResponse
    {
        return ApiResponse::success($this->ledDisplayService->getMarketDisplay($market));
    }
}