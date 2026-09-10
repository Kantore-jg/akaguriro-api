<?php

namespace App\Services\Commerce;

use App\Models\CommerceProduct;
use App\Models\Stock;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockInventoryService
{
    public function getOrCreateItem(Stock $stock, int $productId): StockItem
    {
        return StockItem::firstOrCreate(
            ['stock_id' => $stock->id, 'product_id' => $productId],
            ['quantity' => 0, 'avg_purchase_price' => 0]
        );
    }

    public function increase(
        Stock $stock,
        CommerceProduct $product,
        float $qty,
        float $unitCost,
        string $type,
        ?string $reason,
        User $user,
        ?object $reference = null
    ): StockMovement {
        if ($qty <= 0) {
            throw ValidationException::withMessages(['quantity' => ['La quantité doit être positive.']]);
        }

        return DB::transaction(function () use ($stock, $product, $qty, $unitCost, $type, $reason, $user, $reference) {
            $item = $this->getOrCreateItem($stock, $product->id);
            $item = StockItem::query()->whereKey($item->id)->lockForUpdate()->first();

            $oldQty = (float) $item->quantity;
            $oldAvg = (float) $item->avg_purchase_price;
            $newQty = $oldQty + $qty;
            $newAvg = $newQty > 0
                ? (($oldQty * $oldAvg) + ($qty * $unitCost)) / $newQty
                : $unitCost;

            $item->update([
                'quantity' => $newQty,
                'avg_purchase_price' => round($newAvg, 2),
            ]);

            $product->update(['purchase_price' => $unitCost]);

            return $this->recordMovement($stock, $product, $qty, $unitCost, $type, $reason, $user, $reference);
        });
    }

    public function decrease(
        Stock $stock,
        CommerceProduct $product,
        float $qty,
        string $type,
        ?string $reason,
        User $user,
        ?object $reference = null,
        ?float $unitCost = null
    ): StockMovement {
        if ($qty <= 0) {
            throw ValidationException::withMessages(['quantity' => ['La quantité doit être positive.']]);
        }

        return DB::transaction(function () use ($stock, $product, $qty, $type, $reason, $user, $reference, $unitCost) {
            $item = $this->getOrCreateItem($stock, $product->id);
            $item = StockItem::query()->whereKey($item->id)->lockForUpdate()->first();

            if ((float) $item->quantity < $qty) {
                throw ValidationException::withMessages([
                    'quantity' => ["Stock insuffisant pour {$product->name} (dispo: {$item->quantity})."],
                ]);
            }

            $cost = $unitCost ?? (float) $item->avg_purchase_price;
            $item->update(['quantity' => (float) $item->quantity - $qty]);

            return $this->recordMovement($stock, $product, $qty, $cost, $type, $reason, $user, $reference);
        });
    }

    private function recordMovement(
        Stock $stock,
        CommerceProduct $product,
        float $qty,
        float $unitCost,
        string $type,
        ?string $reason,
        User $user,
        ?object $reference
    ): StockMovement {
        return StockMovement::create([
            'commerce_id' => $stock->commerce_id,
            'stock_id' => $stock->id,
            'product_id' => $product->id,
            'type' => $type,
            'reason' => $reason,
            'quantity' => $qty,
            'unit_cost' => $unitCost,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->id,
            'user_id' => $user->id,
            'moved_at' => now(),
        ]);
    }
}
