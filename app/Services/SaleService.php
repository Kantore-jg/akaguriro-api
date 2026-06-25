<?php

namespace App\Services;

use App\Enums\PaymentType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function list(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = Sale::query()->with(['merchant', 'market', 'place', 'items.product']);

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['market_id'])) {
            $query->where('market_id', $filters['market_id']);
        }

        if (! empty($filters['payment_type'])) {
            $query->where('payment_type', $filters['payment_type']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('client_phone', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(User $merchant, array $data): Sale
    {
        return DB::transaction(function () use ($merchant, $data) {
            $items = $data['items'];
            $productIds = collect($items)->pluck('product_id')->unique()->values();
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->where('user_id', $merchant->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages([
                    'items' => ['Un ou plusieurs produits sont introuvables ou ne vous appartiennent pas.'],
                ]);
            }

            $subtotal = 0;
            $lineItems = [];

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];

                if ($quantity < 1) {
                    throw ValidationException::withMessages([
                        'items' => ['La quantité doit être au moins 1.'],
                    ]);
                }

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ["Stock insuffisant pour « {$product->name} » (disponible : {$product->stock})."],
                    ]);
                }

                $lineTotal = round($product->price * $quantity, 2);
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $sale = Sale::create([
                'user_id' => $merchant->id,
                'market_id' => $data['market_id'],
                'place_id' => $data['place_id'] ?? null,
                'invoice_number' => $this->generateInvoiceNumber(),
                'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'] ?? null,
                'client_email' => $data['client_email'] ?? null,
                'payment_type' => PaymentType::from($data['payment_type']),
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lineItems as $line) {
                $product = $line['product'];

                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_unit' => $product->unit,
                    'unit_price' => $product->price,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);

                $product->decrement('stock', $line['quantity']);

                if ($product->fresh()->stock <= 0) {
                    $product->update(['available' => false]);
                }
            }

            $this->activityLog->log('sale.created', $sale);

            return $sale->load(['merchant', 'market', 'place', 'items.product']);
        });
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'FAC-'.now()->format('Ymd');
        $last = Sale::query()
            ->where('invoice_number', 'like', "{$prefix}-%")
            ->orderByDesc('id')
            ->value('invoice_number');

        $sequence = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('%s-%04d', $prefix, $sequence);
    }
}