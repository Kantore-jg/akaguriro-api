<?php

namespace App\Services\Commerce;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Commerce;
use App\Models\CommerceSale;
use App\Models\StockItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommerceReportService
{
    public function report(Commerce $commerce, string $type, array $filters = []): array
    {
        return match ($type) {
            'sales' => $this->salesReport($commerce, $filters),
            'stock' => $this->stockReport($commerce, $filters),
            'financial' => $this->financialReport($commerce, $filters),
            'benefits', 'bénéfices', 'profits' => $this->benefitsReport($commerce, $filters),
            default => throw ValidationException::withMessages([
                'type' => ['Type de rapport invalide. Valeurs: sales, stock, financial, benefits.'],
            ]),
        };
    }

    private function benefitsReport(Commerce $commerce, array $filters): array
    {
        $sales = $this->salesReport($commerce, $filters);

        return [
            'summary' => [
                'revenue' => $sales['summary']['total_revenue'],
                'cost' => $sales['summary']['total_cost'],
                'gross_margin' => $sales['summary']['gross_margin'],
                'margin_rate' => $sales['summary']['total_revenue'] > 0
                    ? round(($sales['summary']['gross_margin'] / $sales['summary']['total_revenue']) * 100, 2)
                    : 0,
            ],
            'by_day' => $sales['by_day'],
        ];
    }

    private function salesReport(Commerce $commerce, array $filters): array
    {
        $query = CommerceSale::query()
            ->where('commerce_id', $commerce->id);

        $this->applyDateRange($query, 'sold_at', $filters);

        $summary = (clone $query)->select([
            DB::raw('COUNT(*) as sales_count'),
            DB::raw('COALESCE(SUM(total_amount), 0) as total_revenue'),
            DB::raw('COALESCE(SUM(total_cost), 0) as total_cost'),
            DB::raw('COALESCE(SUM(total_amount - total_cost), 0) as gross_margin'),
        ])->first();

        $byPayment = (clone $query)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as amount'))
            ->groupBy('payment_method')
            ->get();

        $byDay = (clone $query)
            ->select(DB::raw('DATE(sold_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as amount'))
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('date')
            ->get();

        return [
            'summary' => [
                'sales_count' => (int) ($summary->sales_count ?? 0),
                'total_revenue' => round((float) ($summary->total_revenue ?? 0), 2),
                'total_cost' => round((float) ($summary->total_cost ?? 0), 2),
                'gross_margin' => round((float) ($summary->gross_margin ?? 0), 2),
            ],
            'by_payment_method' => $byPayment,
            'by_day' => $byDay,
        ];
    }

    private function stockReport(Commerce $commerce, array $filters): array
    {
        $movementQuery = StockMovement::query()
            ->where('commerce_id', $commerce->id)
            ->with(['product', 'stock']);

        $this->applyDateRange($movementQuery, 'moved_at', $filters);

        $byType = (clone $movementQuery)
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(quantity * unit_cost) as total_value'))
            ->groupBy('type')
            ->get();

        $inventory = StockItem::query()
            ->whereHas('stock', fn ($q) => $q->where('commerce_id', $commerce->id))
            ->with(['product', 'stock'])
            ->get()
            ->map(fn ($item) => [
                'stock_id' => $item->stock_id,
                'stock_name' => $item->stock->name,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'quantity' => (float) $item->quantity,
                'avg_purchase_price' => (float) $item->avg_purchase_price,
                'capital_value' => round((float) $item->quantity * (float) $item->avg_purchase_price, 2),
            ]);

        return [
            'movements_by_type' => $byType,
            'inventory' => $inventory,
        ];
    }

    private function financialReport(Commerce $commerce, array $filters): array
    {
        $registerIds = CashRegister::query()
            ->where('commerce_id', $commerce->id)
            ->pluck('id');

        $sessionIds = CashSession::query()
            ->whereIn('cash_register_id', $registerIds)
            ->pluck('id');

        $query = CashMovement::query()
            ->whereIn('cash_session_id', $sessionIds);

        $this->applyDateRange($query, 'moved_at', $filters);

        $byType = (clone $query)
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->type => [
                'count' => (int) $row->count,
                'total' => round((float) $row->total, 2),
            ]]);

        $salesRevenue = round((float) ($byType['SALE']['total'] ?? 0), 2);
        $cashIn = round((float) ($byType['CASH_IN']['total'] ?? 0), 2);
        $cashOut = round((float) ($byType['CASH_OUT']['total'] ?? 0), 2);
        $expenses = round((float) ($byType['EXPENSE']['total'] ?? 0), 2);
        $withdrawals = round((float) ($byType['WITHDRAWAL']['total'] ?? 0), 2);

        return [
            'by_type' => $byType,
            'net_cash_flow' => round($salesRevenue + $cashIn - $cashOut - $expenses - $withdrawals, 2),
            'totals' => [
                'sales' => $salesRevenue,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'expenses' => $expenses,
                'withdrawals' => $withdrawals,
            ],
        ];
    }

    private function applyDateRange($query, string $column, array $filters): void
    {
        if (! empty($filters['from'])) {
            $query->whereDate($column, '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate($column, '<=', $filters['to']);
        }
    }
}
