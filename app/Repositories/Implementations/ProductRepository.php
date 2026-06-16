<?php

namespace App\Repositories\Implementations;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['merchant', 'market', 'place', 'category', 'images']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['market_id'])) {
            $query->where('market_id', $filters['market_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['available'])) {
            $query->where('available', (bool) $filters['available']);
        }

        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';
        $query->orderBy($sort, $direction);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return Product::with(['merchant', 'market', 'place', 'category', 'images'])->find($id);
    }

    public function getTrending(int $limit = 10): Collection
    {
        return Product::query()
            ->where('available', true)
            ->orderByDesc('view_count')
            ->limit($limit)
            ->with(['merchant', 'images'])
            ->get();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh(['merchant', 'market', 'place', 'category', 'images']);
    }

    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }
}