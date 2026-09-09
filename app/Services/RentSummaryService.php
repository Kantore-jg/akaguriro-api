<?php

namespace App\Services;

use App\Enums\PlaceStatus;
use App\Enums\ReceiptStatus;
use App\Models\PaymentReceipt;
use App\Models\Place;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class RentSummaryService
{
    public function __construct(private FileStorageService $fileStorage) {}

    public function summarize(array $filters, User $actor): array
    {
        $year = (int) ($filters['year'] ?? now()->year);
        $month = (int) ($filters['month'] ?? now()->month);
        $marketId = $this->resolveMarketId($filters['market_id'] ?? null, $actor);
        $paymentMethodId = ! empty($filters['payment_method_id'])
            ? (int) $filters['payment_method_id']
            : null;

        if ($month < 1 || $month > 12) {
            throw ValidationException::withMessages([
                'month' => ['Le mois doit être entre 1 et 12.'],
            ]);
        }

        $placesQuery = Place::query()
            ->with(['block', 'chief', 'market'])
            ->where('status', PlaceStatus::Occupied)
            ->whereNotNull('chief_user_id');

        if ($marketId) {
            $placesQuery->where('market_id', $marketId);
        }

        $places = $placesQuery->orderBy('number')->get();

        $receiptsQuery = PaymentReceipt::query()
            ->with(['paymentMethod', 'user', 'place'])
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->whereIn('status', [ReceiptStatus::Pending->value, ReceiptStatus::Approved->value]);

        if ($marketId) {
            $receiptsQuery->where('market_id', $marketId);
        }

        $receipts = $receiptsQuery->get()->groupBy('place_id');

        $period = Carbon::create($year, $month, 1)->startOfMonth();
        $isPastPeriod = $period->lt(now()->startOfMonth());

        $rows = [];
        $expectedAmount = 0;
        $paidAmount = 0;
        $pendingAmount = 0;
        $paidCount = 0;
        $pendingCount = 0;
        $unpaidCount = 0;
        $lateCount = 0;
        $byMethod = [];

        foreach ($places as $place) {
            $rentAmount = (int) ($place->block?->rent_amount ?? 0);
            $expectedAmount += $rentAmount;

            $placeReceipts = $receipts->get($place->id, collect());
            $approved = $placeReceipts->firstWhere('status', ReceiptStatus::Approved);
            $pending = $placeReceipts->firstWhere('status', ReceiptStatus::Pending);

            $receipt = $approved ?? $pending;
            $status = 'unpaid';

            if ($approved) {
                $status = 'paid';
                $paidAmount += (float) $approved->amount;
                $paidCount++;

                $methodId = $approved->payment_method_id ?: 0;
                $methodName = $approved->paymentMethod?->name ?? 'Non renseigné';
                if (! isset($byMethod[$methodId])) {
                    $byMethod[$methodId] = [
                        'id' => $methodId ?: null,
                        'name' => $methodName,
                        'paid_amount' => 0,
                        'paid_count' => 0,
                    ];
                }
                $byMethod[$methodId]['paid_amount'] += (float) $approved->amount;
                $byMethod[$methodId]['paid_count']++;
            } elseif ($pending) {
                $status = 'pending';
                $pendingAmount += (float) $pending->amount;
                $pendingCount++;
            } elseif ($isPastPeriod) {
                $status = 'late';
                $lateCount++;
            } else {
                $unpaidCount++;
            }

            if ($paymentMethodId) {
                if (! $receipt || (int) $receipt->payment_method_id !== $paymentMethodId) {
                    continue;
                }
            }

            $rows[] = [
                'place_id' => $place->id,
                'place_number' => $place->number,
                'market_id' => $place->market_id,
                'market_name' => $place->market?->name,
                'block_id' => $place->market_block_id,
                'block_name' => $place->block?->name,
                'merchant_id' => $place->chief_user_id,
                'merchant_name' => $place->chief?->name,
                'rent_amount' => $rentAmount,
                'status' => $status,
                'receipt' => $receipt ? [
                    'id' => $receipt->id,
                    'amount' => (float) $receipt->amount,
                    'status' => $receipt->status instanceof ReceiptStatus
                        ? $receipt->status->value
                        : $receipt->status,
                    'file_url' => $this->fileStorage->url($receipt->file_path),
                    'payment_method_id' => $receipt->payment_method_id,
                    'payment_method_name' => $receipt->paymentMethod?->name,
                ] : null,
            ];
        }

        // When filtering by payment method, recompute expected from filtered paid/pending rows only
        // for list display — but keep global totals for the period without method filter for KPI.
        // Plan: filter affects list; totals should still reflect full market unless filtered.
        if ($paymentMethodId) {
            $expectedAmount = collect($rows)->sum('rent_amount');
            $paidAmount = collect($rows)->where('status', 'paid')->sum(fn ($r) => $r['receipt']['amount'] ?? 0);
            $pendingAmount = collect($rows)->where('status', 'pending')->sum(fn ($r) => $r['receipt']['amount'] ?? 0);
            $paidCount = collect($rows)->where('status', 'paid')->count();
            $pendingCount = collect($rows)->where('status', 'pending')->count();
            $unpaidCount = collect($rows)->where('status', 'unpaid')->count();
            $lateCount = collect($rows)->where('status', 'late')->count();
        }

        return [
            'year' => $year,
            'month' => $month,
            'market_id' => $marketId,
            'totals' => [
                'expected_amount' => $expectedAmount,
                'paid_amount' => $paidAmount,
                'pending_amount' => $pendingAmount,
                'paid_count' => $paidCount,
                'pending_count' => $pendingCount,
                'unpaid_count' => $unpaidCount,
                'late_count' => $lateCount,
                'occupied_places' => $places->count(),
            ],
            'by_payment_method' => array_values($byMethod),
            'places' => $rows,
        ];
    }

    private function resolveMarketId(mixed $marketId, User $actor): ?int
    {
        if ($actor->managed_market_id && ! $actor->can('manage_markets')) {
            return (int) $actor->managed_market_id;
        }

        return $marketId ? (int) $marketId : null;
    }
}
