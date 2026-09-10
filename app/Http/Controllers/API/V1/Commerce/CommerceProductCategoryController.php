<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StoreCategoryRequest;
use App\Models\CommerceProductCategory;
use App\Services\Commerce\CommerceProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommerceProductCategoryController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private CommerceProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        $categories = $this->productService->listCategories($this->commerce($request));

        return ApiResponse::success($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->productService->createCategory(
            $this->commerce($request),
            $request->validated()
        );

        return ApiResponse::success($category, 'Catégorie créée', 201);
    }

    public function update(StoreCategoryRequest $request, CommerceProductCategory $category): JsonResponse
    {
        $this->assertBelongs($request, $category);

        $category = $this->productService->updateCategory($category, $request->validated());

        return ApiResponse::success($category, 'Catégorie mise à jour');
    }

    public function destroy(Request $request, CommerceProductCategory $category): JsonResponse
    {
        $this->assertBelongs($request, $category);
        $this->productService->deleteCategory($category);

        return ApiResponse::success(null, 'Catégorie supprimée');
    }

    private function assertBelongs(Request $request, CommerceProductCategory $category): void
    {
        if ((int) $category->commerce_id !== (int) $this->commerce($request)->id) {
            throw ValidationException::withMessages([
                'category' => ['Catégorie introuvable pour ce commerce.'],
            ]);
        }
    }
}
