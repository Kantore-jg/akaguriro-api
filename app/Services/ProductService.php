<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSearch;
use App\Models\ProductView;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private FileStorageService $fileStorage,
        private ActivityLogService $activityLog,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginate($filters, $perPage);
    }

    public function getById(int $id): ?Product
    {
        return $this->productRepository->findById($id);
    }

    public function getTrending(int $limit = 10): Collection
    {
        return $this->productRepository->getTrending($limit);
    }

    public function create(array $data, array $images = []): Product
    {
        $product = $this->productRepository->create($data);
        $this->storeImages($product, $images);
        $this->activityLog->log('product.created', $product);

        return $product->load(['merchant', 'market', 'place', 'category', 'images']);
    }

    public function update(Product $product, array $data, array $images = []): Product
    {
        $product = $this->productRepository->update($product, $data);

        if (! empty($images)) {
            $this->storeImages($product, $images);
        }

        $this->activityLog->log('product.updated', $product);

        return $product->fresh(['merchant', 'market', 'place', 'category', 'images']);
    }

    public function delete(Product $product): bool
    {
        foreach ($product->images as $image) {
            $this->fileStorage->delete($image->path);
        }

        $this->activityLog->log('product.deleted', $product);

        return $this->productRepository->delete($product);
    }

    public function recordView(Product $product, ?int $userId = null, ?string $ip = null): void
    {
        ProductView::create([
            'product_id' => $product->id,
            'market_id' => $product->market_id,
            'user_id' => $userId,
            'ip_address' => $ip,
        ]);

        $product->increment('view_count');
    }

    public function recordSearch(string $query, array $context = []): void
    {
        ProductSearch::create([
            'query' => $query,
            'product_id' => $context['product_id'] ?? null,
            'market_id' => $context['market_id'] ?? null,
            'user_id' => $context['user_id'] ?? null,
            'ip_address' => $context['ip_address'] ?? null,
        ]);
    }

    private function storeImages(Product $product, array $images): void
    {
        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($images as $index => $image) {
            if (! $image instanceof UploadedFile) {
                continue;
            }

            $path = $this->fileStorage->store($image, 'products');

            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'is_primary' => ! $hasPrimary && $index === 0,
                'sort_order' => $index,
            ]);

            if (! $hasPrimary && $index === 0) {
                $hasPrimary = true;
            }
        }
    }
}