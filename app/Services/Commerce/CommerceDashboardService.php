<?php

namespace App\Services\Commerce;

use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Commerce;
use App\Models\CommerceProduct;
use App\Models\CommerceSale;
use App\Models\StockItem;
use Illuminate\Support\Facades\DB;

class CommerceDashboardService
{
    public function __construct(private CashSessionService $cashSessions) {}

    public function getKpis(Commerce $commerce): array
    {
        $today = now()->toDateString();

        $salesToday = CommerceSale::query()
            ->where('commerce_id', $commerce->id)
            ->whereDate('sold_at', $today);

        $todayCa = (float) $salesToday->sum('total_amount');
        $salesCount = $salesToday->count();

        $stockStats = StockItem::query()
            ->whereHas('stock', fn ($q) => $q->where('commerce_id', $commerce->id))
            ->select([
                DB::raw('COALESCE(SUM(quantity), 0) as total_qty'),
                DB::raw('COALESCE(SUM(quantity * avg_purchase_price), 0) as capital'),
            ])
            ->first();

        $stockQtySum = (float) ($stockStats->total_qty ?? 0);
        $capital = round((float) ($stockStats->capital ?? 0), 2);

        $saleValue = (float) StockItem::query()
            ->whereHas('stock', fn ($q) => $q->where('commerce_id', $commerce->id))
            ->join('commerce_products', 'commerce_products.id', '=', 'stock_items.product_id')
            ->selectRaw('COALESCE(SUM(stock_items.quantity * commerce_products.sale_price), 0) as sale_value')
            ->value('sale_value');

        $saleValue = round($saleValue, 2);
        $margin = round($saleValue - $capital, 2);

        $lowStockAlerts = $this->lowStockAlerts($commerce);
        $openCashBalance = $this->openCashBalance($commerce);

        return [
            'today_ca' => round($todayCa, 2),
            'sales_count' => $salesCount,
            'stock_qty_sum' => round($stockQtySum, 3),
            'capital' => $capital,
            'sale_value' => $saleValue,
            'margin' => $margin,
            'low_stock_alerts' => $lowStockAlerts,
            'open_cash_balance' => $openCashBalance,
        ];
    }

    private function lowStockAlerts(Commerce $commerce): array
    {
        $products = CommerceProduct::query()
            ->where('commerce_id', $commerce->id)
            ->where('status', 'active')
            ->where('min_stock', '>', 0)
            ->get(['id', 'name', 'sku', 'min_stock']);

        $alerts = [];

        foreach ($products as $product) {
            $totalQty = (float) StockItem::query()
                ->where('product_id', $product->id)
                ->whereHas('stock', fn ($q) => $q->where('commerce_id', $commerce->id))
                ->sum('quantity');

            if ($totalQty < (float) $product->min_stock) {
                $alerts[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => round($totalQty, 3),
                    'min_stock' => (float) $product->min_stock,
                ];
            }
        }

        return $alerts;
    }

    private function openCashBalance(Commerce $commerce): float
    {
        $registerIds = CashRegister::query()
            ->where('commerce_id', $commerce->id)
            ->pluck('id');

        $sessions = CashSession::query()
            ->whereIn('cash_register_id', $registerIds)
            ->where('status', 'open')
            ->with('movements')
            ->get();

        $balance = 0;

        foreach ($sessions as $session) {
            $balance += $this->cashSessions->calculateTheoreticalBalance($session);
        }

        return round($balance, 2);
    }
}
