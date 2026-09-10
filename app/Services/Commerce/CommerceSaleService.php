<?php

namespace App\Services\Commerce;

use App\Models\CashRegister;
use App\Models\Commerce;
use App\Models\CommerceProduct;
use App\Models\CommerceSale;
use App\Models\CommerceSaleItem;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommerceSaleService
{
    public function __construct(
        private StockInventoryService $inventory,
        private CashSessionService $cashSessions,
    ) {}

    public function list(Commerce $commerce, array $filters = []): Collection
    {
        $query = CommerceSale::query()
            ->where('commerce_id', $commerce->id)
            ->with(['items.product', 'stock', 'user'])
            ->orderByDesc('sold_at');

        if (! empty($filters['stock_id'])) {
            $query->where('stock_id', $filters['stock_id']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('sold_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('sold_at', '<=', $filters['to']);
        }

        return $query->get();
    }

    public function show(Commerce $commerce, CommerceSale $sale): CommerceSale
    {
        $this->assertBelongsToCommerce($commerce, $sale);

        return $sale->load(['items.product', 'stock', 'user', 'cashSession']);
    }

    public function create(Commerce $commerce, array $data, User $user): CommerceSale
    {
        return DB::transaction(function () use ($commerce, $data, $user) {
            $stock = Stock::query()
                ->where('commerce_id', $commerce->id)
                ->findOrFail($data['stock_id']);

            $paymentMethod = $data['payment_method'] ?? 'cash';
            $cashSession = null;

            if ($paymentMethod !== 'credit') {
                $register = CashRegister::query()
                    ->where('commerce_id', $commerce->id)
                    ->findOrFail($data['cash_register_id']);

                $cashSession = $register->currentOpenSession();
                if (! $cashSession) {
                    throw ValidationException::withMessages([
                        'cash_register_id' => ['Aucune session de caisse ouverte pour cette caisse.'],
                    ]);
                }
            }

            $totalAmount = 0;
            $totalCost = 0;
            $lineItems = [];

            foreach ($data['items'] as $item) {
                $product = CommerceProduct::query()
                    ->where('commerce_id', $commerce->id)
                    ->findOrFail($item['product_id']);

                $qty = (float) $item['quantity'];
                $unitPrice = (float) ($item['unit_price'] ?? $product->sale_price);
                $lineTotal = round($qty * $unitPrice, 2);
                $totalAmount += $lineTotal;

                $stockItem = $this->inventory->getOrCreateItem($stock, $product->id);
                $unitCost = (float) $stockItem->avg_purchase_price;
                $totalCost += round($qty * $unitCost, 2);

                $lineItems[] = compact('product', 'qty', 'unitPrice', 'unitCost', 'lineTotal');
            }

            $sale = CommerceSale::create([
                'commerce_id' => $commerce->id,
                'stock_id' => $stock->id,
                'cash_session_id' => $cashSession?->id,
                'payment_method' => $paymentMethod,
                'total_amount' => round($totalAmount, 2),
                'total_cost' => round($totalCost, 2),
                'user_id' => $user->id,
                'sold_at' => $data['sold_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lineItems as $line) {
                CommerceSaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['qty'],
                    'unit_price' => $line['unitPrice'],
                    'unit_cost' => $line['unitCost'],
                    'line_total' => $line['lineTotal'],
                ]);

                $this->inventory->decrease(
                    $stock,
                    $line['product'],
                    $line['qty'],
                    'SALE',
                    null,
                    $user,
                    $sale,
                    $line['unitCost']
                );
            }

            if ($paymentMethod !== 'credit' && $cashSession) {
                $this->cashSessions->recordMovement(
                    $cashSession,
                    'SALE',
                    (float) $sale->total_amount,
                    $data['cash_motif'] ?? 'Vente POS',
                    $user,
                    $sale
                );
            }

            return $sale->fresh(['items.product', 'stock', 'user', 'cashSession']);
        });
    }

    private function assertBelongsToCommerce(Commerce $commerce, CommerceSale $sale): void
    {
        if ((int) $sale->commerce_id !== (int) $commerce->id) {
            throw ValidationException::withMessages([
                'sale' => ['Vente introuvable pour ce commerce.'],
            ]);
        }
    }
}
