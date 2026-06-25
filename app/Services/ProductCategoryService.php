<?php

namespace App\Services;

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
            ->withCount('products')
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

        return $category->fresh()->loadCount('products');
    }

    public function delete(ProductCategory $category): bool
    {
        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => ['Cette catégorie contient des sous-catégories. Supprimez-les d\'abord.'],
            ]);
        }

        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => ['Cette catégorie est encore utilisée par des produits. Réaffectez-les d\'abord.'],
            ]);
        }

        if ($category->markets()->exists()) {
            throw ValidationException::withMessages([
                'category' => ['Cette catégorie est encore associée à des marchés. Retirez-la d\'abord.'],
            ]);
        }

        return (bool) $category->delete();
    }
}