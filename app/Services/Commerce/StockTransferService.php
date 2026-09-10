<?php

namespace App\Services\Commerce;

use App\Models\Commerce;
use App\Models\CommerceProduct;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function __construct(private StockInventoryService $inventory) {}

    public function list(Commerce $commerce, array $filters = []): Collection
    {
        $query = StockTransfer::query()
            ->where('commerce_id', $commerce->id)
            ->with(['fromStock', 'toStock', 'items.product', 'user'])
            ->orderByDesc('transferred_at');

        if (! empty($filters['from_stock_id'])) {
            $query->where('from_stock_id', $filters['from_stock_id']);
        }

        if (! empty($filters['to_stock_id'])) {
            $query->where('to_stock_id', $filters['to_stock_id']);
        }

        return $query->get();
    }

    public function show(Commerce $commerce, StockTransfer $transfer): StockTransfer
    {
        $this->assertBelongsToCommerce($commerce, $transfer);

        return $transfer->load(['fromStock', 'toStock', 'items.product', 'user']);
    }

    public function create(Commerce $commerce, array $data, User $user): StockTransfer
    {
        return DB::transaction(function () use ($commerce, $data, $user) {
            $fromStock = Stock::query()
                ->where('commerce_id', $commerce->id)
                ->findOrFail($data['from_stock_id']);

            $toStock = Stock::query()
                ->where('commerce_id', $commerce->id)
                ->findOrFail($data['to_stock_id']);

            if ((int) $fromStock->id === (int) $toStock->id) {
                throw ValidationException::withMessages([
                    'to_stock_id' => ['Le stock source et destination doivent être différents.'],
                ]);
            }

            $transfer = StockTransfer::create([
                'commerce_id' => $commerce->id,
                'transfer_no' => $this->generateTransferNo($commerce),
                'from_stock_id' => $fromStock->id,
                'to_stock_id' => $toStock->id,
                'status' => 'completed',
                'user_id' => $user->id,
                'notes' => $data['notes'] ?? null,
                'transferred_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $product = CommerceProduct::query()
                    ->where('commerce_id', $commerce->id)
                    ->findOrFail($item['product_id']);

                $qty = (float) $item['quantity'];
                $stockItem = $this->inventory->getOrCreateItem($fromStock, $product->id);
                $unitCost = isset($item['unit_cost'])
                    ? (float) $item['unit_cost']
                    : (float) $stockItem->avg_purchase_price;

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                ]);

                $this->inventory->decrease(
                    $fromStock,
                    $product,
                    $qty,
                    'TRANSFER_OUT',
                    null,
                    $user,
                    $transfer,
                    $unitCost
                );

                $this->inventory->increase(
                    $toStock,
                    $product,
                    $qty,
                    $unitCost,
                    'TRANSFER_IN',
                    null,
                    $user,
                    $transfer
                );
            }

            return $transfer->fresh(['fromStock', 'toStock', 'items.product', 'user']);
        });
    }

    private function generateTransferNo(Commerce $commerce): string
    {
        $date = now()->format('Ymd');
        $prefix = "TRF-{$date}-";

        $last = StockTransfer::query()
            ->where('commerce_id', $commerce->id)
            ->where('transfer_no', 'like', "{$prefix}%")
            ->orderByDesc('transfer_no')
            ->lockForUpdate()
            ->first();

        $seq = $last ? ((int) substr($last->transfer_no, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function assertBelongsToCommerce(Commerce $commerce, StockTransfer $transfer): void
    {
        if ((int) $transfer->commerce_id !== (int) $commerce->id) {
            throw ValidationException::withMessages([
                'transfer' => ['Transfert introuvable pour ce commerce.'],
            ]);
        }
    }
}
