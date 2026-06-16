<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->filled('search')) {
            $this->productService->recordSearch($request->search, [
                'market_id' => $request->market_id,
                'user_id' => $request->user()?->id,
                'ip_address' => $request->ip(),
            ]);
        }

        $products = $this->productService->list($request->only([
            'search', 'market_id', 'user_id', 'category_id', 'available', 'sort', 'direction',
        ]), (int) $request->get('per_page', 15));

        return ApiResponse::success(ProductResource::collection($products));
    }

    public function trending(): JsonResponse
    {
        $products = $this->productService->getTrending();

        return ApiResponse::success(ProductResource::collection($products));
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->authorize('view', $product);
        $this->productService->recordView($product, $request->user()?->id, $request->ip());

        return ApiResponse::success(new ProductResource(
            $product->load(['merchant', 'market', 'place', 'category', 'images'])
        ));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $product = $this->productService->create(
            $data,
            $request->file('images', []),
        );

        return ApiResponse::success(new ProductResource($product), 'Produit créé', 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'available' => ['nullable', 'boolean'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
        ]);

        $product = $this->productService->update(
            $product,
            $data,
            $request->file('images', []),
        );

        return ApiResponse::success(new ProductResource($product), 'Produit mis à jour');
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);
        $this->productService->delete($product);

        return ApiResponse::success(null, 'Produit supprimé');
    }
}