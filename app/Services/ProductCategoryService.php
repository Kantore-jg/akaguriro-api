<?php

namespace App\Services;

use App\Models\Place;
use App\Models\PlaceRequest;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ProductCategoryService
{
    public function listActive(): Collection
    {
        return ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function listAll(): Collection
    {
        return ProductCategory::query()
            ->withCount(['products', 'markets'])
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): ProductCategory
    {
        return ProductCategory::create($data);
    }

    public function update(ProductCategory $category, array $data): ProductCategory
    {
        $category->update($data);

        return $category->fresh()->loadCount(['products', 'markets']);
    }

    public function delete(ProductCategory $category): bool
    {
        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => ['Cette catégorie contient des sous-catégories. Supprimez-les d\'abord.'],
            ]);
        }

        $this->purgeCategoryReferences($category);

        return (bool) $category->delete();
    }

    private function purgeCategoryReferences(ProductCategory $category): void
    {
        $categoryId = (int) $category->id;

        Place::query()
            ->whereNotNull('product_category_ids')
            ->get()
            ->each(function (Place $place) use ($categoryId) {
                $ids = array_values(array_filter(
                    $place->product_category_ids ?? [],
                    fn ($id) => (int) $id !== $categoryId,
                ));

                if ($ids === ($place->product_category_ids ?? [])) {
                    return;
                }

                $place->update([
                    'product_category_ids' => $ids ?: null,
                    'category' => $this->labelsForCategoryIds($ids),
                ]);
            });

        PlaceRequest::query()
            ->whereNotNull('product_category_ids')
            ->get()
            ->each(function (PlaceRequest $request) use ($categoryId) {
                $ids = array_values(array_filter(
                    $request->product_category_ids ?? [],
                    fn ($id) => (int) $id !== $categoryId,
                ));

                if ($ids === ($request->product_category_ids ?? [])) {
                    return;
                }

                $request->update([
                    'product_category_ids' => $ids ?: null,
                    'category' => $this->labelsForCategoryIds($ids),
                ]);
            });
    }

    private function labelsForCategoryIds(array $ids): ?string
    {
        if (empty($ids)) {
            return null;
        }

        $names = ProductCategory::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return $names ? implode(', ', $names) : null;
    }
}