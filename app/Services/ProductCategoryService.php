<?php

namespace App\Services;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Collection;

class ProductCategoryService
{
    public function listActive(): Collection
    {
        return ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}