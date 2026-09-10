<?php

namespace App\Services\Commerce;

use App\Models\CashRegister;
use App\Models\Commerce;
use App\Models\CommerceProduct;
use App\Models\CommercePurchase;
use App\Models\CommercePurchaseItem;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        private StockInventoryService $inventory,
        private CashSessionService $cashSessions,
    ) {}

    public function list(Commerce $commerce, array $filters = []): Collection
    {
        $query = CommercePurchase::query()
            ->where('commerce_id', $commerce->id)
            ->with(['items.product', 'stock', 'user'])
            ->orderByDesc('purchase_date')
            ->orderByDesc('id');

        if (! empty($filters['stock_id'])) {
            $query->where('stock_id', $filters['stock_id']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('purchase_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('purchase_date', '<=', $filters['to']);
        }

        return $query->get();
    }

    public function show(Commerce $commerce, CommercePurchase $purchase): CommercePurchase
    {
        $this->assertBelongsToCommerce($commerce, $purchase);

        return $purchase->load(['items.product', 'stock', 'user', 'cashRegister', 'cashSession']);
    }

    public function create(Commerce $commerce, array $data, User $user): CommercePurchase
    {
        return DB::transaction(function () use ($commerce, $data, $user) {
            $stock = Stock::query()
                ->where('commerce_id', $commerce->id)
                ->findOrFail($data['stock_id']);

            $paidFromCash = (bool) ($data['paid_from_cash'] ?? false);
            $paymentStatus = $data['payment_status'] ?? 'paid';
            $cashSession = null;
            $cashRegisterId = null;

            if ($paidFromCash && $paymentStatus === 'paid') {
                $register = CashRegister::query()
                    ->where('commerce_id', $commerce->id)
                    ->findOrFail($data['cash_register_id']);

                $cashSession = $register->currentOpenSession();
                if (! $cashSession) {
                    throw ValidationException::withMessages([
                        'cash_register_id' => ['Aucune session de caisse ouverte pour cette caisse.'],
                    ]);
                }
                $cashRegisterId = $register->id;
            }

            $totalAmount = 0;
            $lineItems = [];

            foreach ($data['items'] as $item) {
                $product = CommerceProduct::query()
                    ->where('commerce_id', $commerce->id)
                    ->findOrFail($item['product_id']);

                $qty = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $lineTotal = round($qty * $unitCost, 2);
                $totalAmount += $lineTotal;

                $lineItems[] = compact('product', 'qty', 'unitCost', 'lineTotal');
            }

            $purchase = CommercePurchase::create([
                'commerce_id' => $commerce->id,
                'stock_id' => $stock->id,
                'supplier_name' => $data['supplier_name'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'payment_status' => $paymentStatus,
                'paid_from_cash' => $paidFromCash,
                'cash_register_id' => $cashRegisterId,
                'cash_session_id' => $cashSession?->id,
                'total_amount' => round($totalAmount, 2),
                'user_id' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lineItems as $line) {
                CommercePurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['qty'],
                    'unit_cost' => $line['unitCost'],
                    'line_total' => $line['lineTotal'],
                ]);

                $this->inventory->increase(
                    $stock,
                    $line['product'],
                    $line['qty'],
                    $line['unitCost'],
                    'PURCHASE',
                    null,
                    $user,
                    $purchase
                );
            }

            if ($paidFromCash && $paymentStatus === 'paid' && $cashSession) {
                $this->cashSessions->recordMovement(
                    $cashSession,
                    'CASH_OUT',
                    (float) $purchase->total_amount,
                    $data['cash_motif'] ?? "Achat fournisseur: {$purchase->supplier_name}",
                    $user,
                    $purchase
                );
            }

            return $purchase->fresh(['items.product', 'stock', 'user']);
        });
    }

    private function assertBelongsToCommerce(Commerce $commerce, CommercePurchase $purchase): void
    {
        if ((int) $purchase->commerce_id !== (int) $commerce->id) {
            throw ValidationException::withMessages([
                'purchase' => ['Achat introuvable pour ce commerce.'],
            ]);
        }
    }
}
