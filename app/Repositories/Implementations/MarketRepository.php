<?php

namespace App\Repositories\Implementations;

use App\Models\Market;
use App\Repositories\Contracts\MarketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MarketRepository implements MarketRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Market::query()->withCount(['places', 'products']);

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['direction'] ?? 'asc';
        $query->orderBy($sort, $direction);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Market
    {
        return Market::with(['blocks', 'places.chief'])->find($id);
    }

    public function findBySlug(string $slug): ?Market
    {
        return Market::with(['blocks', 'places.chief'])->where('slug', $slug)->first();
    }

    public function getPopular(int $limit = 5): Collection
    {
        return Market::query()
            ->where('is_active', true)
            ->orderByDesc('visit_count')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): Market
    {
        return Market::create($data);
    }

    public function update(Market $market, array $data): Market
    {
        $market->update($data);

        return $market->fresh();
    }

    public function delete(Market $market): bool
    {
        return (bool) $market->delete();
    }
}