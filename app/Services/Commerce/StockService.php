<?php

namespace App\Services\Commerce;

use App\Models\Commerce;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function list(Commerce $commerce): Collection
    {
        return Stock::query()
            ->where('commerce_id', $commerce->id)
            ->with(['manager', 'items.product'])
            ->orderBy('name')
            ->get();
    }

    public function create(Commerce $commerce, array $data): Stock
    {
        return Stock::create([
            'commerce_id' => $commerce->id,
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'manager_user_id' => $data['manager_user_id'] ?? null,
            'status' => $data['status'] ?? 'active',
        ])->load(['manager', 'items.product']);
    }

    public function update(Stock $stock, array $data): Stock
    {
        $stock->update(collect($data)->only([
            'name', 'location', 'manager_user_id', 'status',
        ])->filter(fn ($v) => $v !== null)->all());

        return $stock->fresh(['manager', 'items.product']);
    }

    public function assertBelongsToCommerce(Commerce $commerce, Stock $stock): void
    {
        if ((int) $stock->commerce_id !== (int) $commerce->id) {
            throw ValidationException::withMessages([
                'stock' => ['Stock introuvable pour ce commerce.'],
            ]);
        }
    }

    public function listMovements(Commerce $commerce, array $filters = []): Collection
    {
        $query = StockMovement::query()
            ->where('commerce_id', $commerce->id)
            ->with(['product', 'stock', 'user'])
            ->orderByDesc('moved_at');

        if (! empty($filters['stock_id'])) {
            $query->where('stock_id', $filters['stock_id']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('moved_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('moved_at', '<=', $filters['to']);
        }

        return $query->get();
    }
}
