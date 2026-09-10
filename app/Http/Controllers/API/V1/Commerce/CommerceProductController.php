<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StoreProductRequest;
use App\Http\Resources\Commerce\CommerceProductResource;
use App\Models\CommerceProduct;
use App\Services\Commerce\CommerceProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommerceProductController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private CommerceProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->listProducts(
            $this->commerce($request),
            $request->only(['category_id', 'status', 'search'])
        );

        return ApiResponse::success(CommerceProductResource::collection($products));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct(
            $this->commerce($request),
            $request->validated()
        );

        return ApiResponse::success(new CommerceProductResource($product), 'Produit créé', 201);
    }

    public function show(Request $request, CommerceProduct $product): JsonResponse
    {
        $this->assertBelongs($request, $product);

        return ApiResponse::success(new CommerceProductResource($product->load('category')));
    }

    public function update(StoreProductRequest $request, CommerceProduct $product): JsonResponse
    {
        $this->assertBelongs($request, $product);

        $product = $this->productService->updateProduct($product, $request->validated());

        return ApiResponse::success(new CommerceProductResource($product), 'Produit mis à jour');
    }

    public function destroy(Request $request, CommerceProduct $product): JsonResponse
    {
        $this->assertBelongs($request, $product);
        $this->productService->deleteProduct($product);

        return ApiResponse::success(null, 'Produit désactivé');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required_without:csv_content', 'file', 'mimes:csv,txt'],
            'csv_content' => ['required_without:file', 'string'],
        ]);

        $content = $request->hasFile('file')
            ? file_get_contents($request->file('file')->getRealPath())
            : $request->input('csv_content');

        $result = $this->productService->importCsv($this->commerce($request), $content);

        return ApiResponse::success($result, 'Import terminé');
    }

    public function export(Request $request): JsonResponse
    {
        $csv = $this->productService->exportCsv($this->commerce($request));

        return ApiResponse::success(['csv' => $csv], 'Export généré');
    }

    private function assertBelongs(Request $request, CommerceProduct $product): void
    {
        if ((int) $product->commerce_id !== (int) $this->commerce($request)->id) {
            throw ValidationException::withMessages([
                'product' => ['Produit introuvable pour ce commerce.'],
            ]);
        }
    }
}
