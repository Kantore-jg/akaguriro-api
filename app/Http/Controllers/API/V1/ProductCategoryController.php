<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCategory\StoreProductCategoryRequest;
use App\Http\Requests\ProductCategory\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
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

    public function manage(): JsonResponse
    {
        $categories = $this->productCategoryService->listAll();

        return ApiResponse::success(ProductCategoryResource::collection($categories));
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $category = $this->productCategoryService->create($request->validated());

        return ApiResponse::success(
            new ProductCategoryResource($category->loadCount('products')),
            'Catégorie créée',
            201,
        );
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        $category = $this->productCategoryService->update($productCategory, $request->validated());

        return ApiResponse::success(new ProductCategoryResource($category), 'Catégorie mise à jour');
    }

    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        $this->productCategoryService->delete($productCategory);

        return ApiResponse::success(null, 'Catégorie supprimée');
    }
}