<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Services\ProductCategoryService;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends Controller
{
    public function __construct(private ProductCategoryService $productCategoryService) {}

    public function index(): JsonResponse
    {
        $categories = $this->productCategoryService->listActive();

        return ApiResponse::success(ProductCategoryResource::collection($categories));
    }
}