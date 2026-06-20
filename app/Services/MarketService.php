<?php

namespace App\Services;

use App\Models\Market;
use App\Models\MarketVisit;
use App\Repositories\Contracts\MarketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class MarketService
{
    public function __construct(
        private MarketRepositoryInterface $marketRepository,
        private FileStorageService $fileStorage,
        private ActivityLogService $activityLog,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->marketRepository->paginate($filters, $perPage);
    }

    public function getById(int $id): ?Market
    {
        return $this->marketRepository->findById($id);
    }

    public function getPopular(int $limit = 5): Collection
    {
        return $this->marketRepository->getPopular($limit);
    }

    public function getStatistics(Market $market): array
    {
        $market->loadCount(['places', 'products']);

        return [
            'total_places' => $market->total_places,
            'occupied_places' => $market->occupied_places,
            'available_places' => max(0, $market->total_places - $market->occupied_places),
            'occupancy_rate' => $market->total_places > 0
                ? round(($market->occupied_places / $market->total_places) * 100, 2)
                : 0,
            'products_count' => $market->products_count,
            'places_count' => $market->places_count,
            'visit_count' => $market->visit_count,
        ];
    }

    public function recordVisit(Market $market, ?int $userId = null, ?string $ip = null): void
    {
        MarketVisit::create([
            'market_id' => $market->id,
            'user_id' => $userId,
            'ip_address' => $ip,
        ]);

        $market->increment('visit_count');
    }

    public function create(array $data, ?UploadedFile $image = null, ?UploadedFile $coverImage = null): Market
    {
        if ($image) {
            $data['image'] = $this->fileStorage->store($image, 'markets/images');
        }

        if ($coverImage) {
            $data['cover_image'] = $this->fileStorage->store($coverImage, 'markets/covers');
        }

        $categoryIds = $data['product_category_ids'] ?? null;
        unset($data['product_category_ids']);

        $market = $this->marketRepository->create($data);

        if (is_array($categoryIds)) {
            $market->productCategories()->sync($categoryIds);
        }

        $this->activityLog->log('market.created', $market);

        return $market->load('productCategories');
    }

    public function update(Market $market, array $data, ?UploadedFile $image = null, ?UploadedFile $coverImage = null): Market
    {
        if ($image) {
            $this->fileStorage->delete($market->image);
            $data['image'] = $this->fileStorage->store($image, 'markets/images');
        }

        if ($coverImage) {
            $this->fileStorage->delete($market->cover_image);
            $data['cover_image'] = $this->fileStorage->store($coverImage, 'markets/covers');
        }

        $categoryIds = $data['product_category_ids'] ?? null;
        unset($data['product_category_ids']);

        $market = $this->marketRepository->update($market, $data);

        if (is_array($categoryIds)) {
            $market->productCategories()->sync($categoryIds);
        }

        $this->activityLog->log('market.updated', $market);

        return $market->load('productCategories');
    }

    public function delete(Market $market): bool
    {
        if ($market->places()->exists()) {
            throw ValidationException::withMessages([
                'market' => ['Ce marché contient encore des emplacements. Supprimez-les d\'abord.'],
            ]);
        }

        if ($market->products()->exists()) {
            throw ValidationException::withMessages([
                'market' => ['Ce marché contient encore des produits. Retirez-les d\'abord.'],
            ]);
        }

        if ($market->admins()->exists()) {
            throw ValidationException::withMessages([
                'market' => ['Ce marché est encore géré par un administrateur. Réaffectez-le d\'abord.'],
            ]);
        }

        $this->activityLog->log('market.deleted', $market);

        return $this->marketRepository->delete($market);
    }
}