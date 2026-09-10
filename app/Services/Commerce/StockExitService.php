<?php

namespace App\Services\Commerce;

use App\Models\Commerce;
use App\Models\CommerceProduct;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockExitService
{
    private const REASONS = ['LOSS', 'DAMAGED', 'INTERNAL_USE', 'ADJUSTMENT', 'OTHER'];

    public function __construct(private StockInventoryService $inventory) {}

    public function create(Commerce $commerce, array $data, User $user): array
    {
        if (! in_array($data['reason'], self::REASONS, true)) {
            throw ValidationException::withMessages([
                'reason' => ['Motif de sortie invalide.'],
            ]);
        }

        return DB::transaction(function () use ($commerce, $data, $user) {
            $stock = Stock::query()
                ->where('commerce_id', $commerce->id)
                ->findOrFail($data['stock_id']);

            $movements = [];

            foreach ($data['items'] as $item) {
                $product = CommerceProduct::query()
                    ->where('commerce_id', $commerce->id)
                    ->findOrFail($item['product_id']);

                $movements[] = $this->inventory->decrease(
                    $stock,
                    $product,
                    (float) $item['quantity'],
                    'EXIT',
                    $data['reason'],
                    $user,
                    null,
                    isset($item['unit_cost']) ? (float) $item['unit_cost'] : null
                );
            }

            return $movements;
        });
    }
}
