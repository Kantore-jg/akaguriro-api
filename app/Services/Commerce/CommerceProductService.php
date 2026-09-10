<?php

namespace App\Services\Commerce;

use App\Models\Commerce;
use App\Models\CommerceProduct;
use App\Models\CommerceProductCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommerceProductService
{
    public function listCategories(Commerce $commerce): Collection
    {
        return CommerceProductCategory::query()
            ->where('commerce_id', $commerce->id)
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    public function createCategory(Commerce $commerce, array $data): CommerceProductCategory
    {
        $slug = $this->uniqueCategorySlug($commerce, $data['name']);

        return CommerceProductCategory::create([
            'commerce_id' => $commerce->id,
            'name' => $data['name'],
            'slug' => $slug,
        ]);
    }

    public function updateCategory(CommerceProductCategory $category, array $data): CommerceProductCategory
    {
        $updates = [];

        if (isset($data['name'])) {
            $updates['name'] = $data['name'];
            $updates['slug'] = $this->uniqueCategorySlug($category->commerce, $data['name'], $category->id);
        }

        $category->update($updates);

        return $category->fresh();
    }

    public function deleteCategory(CommerceProductCategory $category): void
    {
        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => ['Impossible de supprimer une catégorie contenant des produits.'],
            ]);
        }

        $category->delete();
    }

    public function listProducts(Commerce $commerce, array $filters = []): Collection
    {
        $query = CommerceProduct::query()
            ->where('commerce_id', $commerce->id)
            ->with('category')
            ->orderBy('name');

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function createProduct(Commerce $commerce, array $data): CommerceProduct
    {
        $this->assertCategoryBelongsToCommerce($commerce, $data['category_id'] ?? null);

        return CommerceProduct::create([
            'commerce_id' => $commerce->id,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'unit' => $data['unit'] ?? 'pcs',
            'purchase_price' => round((float) ($data['purchase_price'] ?? 0), 2),
            'sale_price' => round((float) ($data['sale_price'] ?? 0), 2),
            'min_stock' => round((float) ($data['min_stock'] ?? 0), 3),
            'max_stock' => isset($data['max_stock']) ? round((float) $data['max_stock'], 3) : null,
            'vat_rate' => round((float) ($data['vat_rate'] ?? 0), 2),
            'status' => $data['status'] ?? 'active',
        ])->load('category');
    }

    public function updateProduct(CommerceProduct $product, array $data): CommerceProduct
    {
        if (array_key_exists('category_id', $data)) {
            $this->assertCategoryBelongsToCommerce($product->commerce, $data['category_id']);
        }

        $product->update(collect($data)->only([
            'category_id', 'name', 'sku', 'barcode', 'unit',
            'purchase_price', 'sale_price', 'min_stock', 'max_stock', 'vat_rate', 'status',
        ])->filter(fn ($v) => $v !== null)->all());

        return $product->fresh('category');
    }

    public function deleteProduct(CommerceProduct $product): void
    {
        $product->update(['status' => 'inactive']);
    }

    public function importCsv(Commerce $commerce, string $csvContent): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csvContent));
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'file' => ['Le fichier CSV est vide ou invalide.'],
            ]);
        }

        $header = str_getcsv(array_shift($lines));
        $expected = ['name', 'sku', 'barcode', 'unit', 'purchase_price', 'sale_price', 'min_stock', 'vat_rate'];
        $normalizedHeader = array_map(fn ($h) => strtolower(trim($h)), $header);

        foreach ($expected as $col) {
            if (! in_array($col, $normalizedHeader, true)) {
                throw ValidationException::withMessages([
                    'file' => ["Colonne manquante dans le CSV: {$col}"],
                ]);
            }
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        DB::transaction(function () use ($commerce, $lines, $normalizedHeader, &$created, &$updated, &$errors) {
            foreach ($lines as $index => $line) {
                if (trim($line) === '') {
                    continue;
                }

                $row = str_getcsv($line);
                $data = array_combine($normalizedHeader, array_pad($row, count($normalizedHeader), ''));

                if (empty($data['name'])) {
                    $errors[] = 'Ligne '.($index + 2).': nom requis';

                    continue;
                }

                $payload = [
                    'name' => $data['name'],
                    'sku' => $data['sku'] ?: null,
                    'barcode' => $data['barcode'] ?: null,
                    'unit' => $data['unit'] ?: 'pcs',
                    'purchase_price' => round((float) ($data['purchase_price'] ?: 0), 2),
                    'sale_price' => round((float) ($data['sale_price'] ?: 0), 2),
                    'min_stock' => round((float) ($data['min_stock'] ?: 0), 3),
                    'vat_rate' => round((float) ($data['vat_rate'] ?: 0), 2),
                    'status' => 'active',
                ];

                $existing = null;
                if ($payload['sku']) {
                    $existing = CommerceProduct::query()
                        ->where('commerce_id', $commerce->id)
                        ->where('sku', $payload['sku'])
                        ->first();
                } elseif ($payload['barcode']) {
                    $existing = CommerceProduct::query()
                        ->where('commerce_id', $commerce->id)
                        ->where('barcode', $payload['barcode'])
                        ->first();
                }

                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    CommerceProduct::create(array_merge($payload, ['commerce_id' => $commerce->id]));
                    $created++;
                }
            }
        });

        return compact('created', 'updated', 'errors');
    }

    public function exportCsv(Commerce $commerce): string
    {
        $products = CommerceProduct::query()
            ->where('commerce_id', $commerce->id)
            ->orderBy('name')
            ->get();

        $lines = ['name,sku,barcode,unit,purchase_price,sale_price,min_stock,vat_rate'];

        foreach ($products as $product) {
            $lines[] = implode(',', [
                $this->escapeCsv($product->name),
                $this->escapeCsv($product->sku ?? ''),
                $this->escapeCsv($product->barcode ?? ''),
                $this->escapeCsv($product->unit),
                $product->purchase_price,
                $product->sale_price,
                $product->min_stock,
                $product->vat_rate,
            ]);
        }

        return implode("\n", $lines);
    }

    private function uniqueCategorySlug(Commerce $commerce, string $name, ?int $exceptId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 1;

        while (CommerceProductCategory::query()
            ->where('commerce_id', $commerce->id)
            ->where('slug', $slug)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function assertCategoryBelongsToCommerce(Commerce $commerce, ?int $categoryId): void
    {
        if ($categoryId === null) {
            return;
        }

        $exists = CommerceProductCategory::query()
            ->where('commerce_id', $commerce->id)
            ->where('id', $categoryId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'category_id' => ['Catégorie introuvable pour ce commerce.'],
            ]);
        }
    }

    private function escapeCsv(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
