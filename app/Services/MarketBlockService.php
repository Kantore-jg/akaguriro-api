<?php

namespace App\Services;

use App\Models\Market;
use App\Models\MarketBlock;
use App\Models\Place;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class MarketBlockService
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function listByMarket(Market $market): Collection
    {
        return MarketBlock::query()
            ->where('market_id', $market->id)
            ->withCount('places')
            ->orderBy('name')
            ->get();
    }

    public function create(Market $market, array $data): MarketBlock
    {
        $block = MarketBlock::create([
            'market_id' => $market->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'total_places' => 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->activityLog->log('market_block.created', $block);

        return $block->loadCount('places');
    }

    public function update(MarketBlock $block, array $data): MarketBlock
    {
        $block->update($data);
        $this->activityLog->log('market_block.updated', $block);

        return $block->fresh()->loadCount('places');
    }

    public function delete(MarketBlock $block): void
    {
        if ($block->places()->exists()) {
            throw ValidationException::withMessages([
                'block' => ['Ce bloc contient encore des emplacements. Supprimez ou réaffectez-les d\'abord.'],
            ]);
        }

        $this->activityLog->log('market_block.deleted', $block);
        $block->delete();
    }

    public function syncPlacesCount(MarketBlock $block): void
    {
        $block->update([
            'total_places' => Place::where('market_block_id', $block->id)->count(),
        ]);
    }
}