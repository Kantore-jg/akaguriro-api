<?php

namespace App\Services;

use App\Enums\PlaceMemberRole;
use App\Enums\PlaceStatus;
use App\Models\Place;
use App\Models\PlaceMember;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PlaceService
{
    public function __construct(
        private ActivityLogService $activityLog,
        private MarketBlockService $blockService,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Place::query()->with(['market', 'block', 'chief', 'members.user']);

        if (! empty($filters['market_id'])) {
            $query->where('market_id', $filters['market_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where('number', 'like', '%'.$filters['search'].'%');
        }

        return $query->orderBy('number')->paginate($perPage);
    }

    public function create(array $data): Place
    {
        if (empty($data['qr_code'])) {
            $data['qr_code'] = 'AKG-'.Str::upper(Str::random(10));
        }

        $place = Place::create($data);
        $this->syncMarketCounts($place->market_id);
        $this->syncBlockCounts($place->market_block_id);
        $this->activityLog->log('place.created', $place);

        return $place->load(['market', 'block', 'chief', 'members.user']);
    }

    public function update(Place $place, array $data): Place
    {
        $previousBlockId = $place->market_block_id;
        $place->update($data);
        $this->syncMarketCounts($place->market_id);
        $this->syncBlockCounts($place->market_block_id);
        if ($previousBlockId && $previousBlockId !== $place->market_block_id) {
            $this->syncBlockCounts($previousBlockId);
        }
        $this->activityLog->log('place.updated', $place);

        return $place->fresh(['market', 'block', 'chief', 'members.user']);
    }

    public function delete(Place $place): void
    {
        $marketId = $place->market_id;
        $blockId = $place->market_block_id;

        $this->activityLog->log('place.deleted', $place);
        $place->delete();

        $this->syncMarketCounts($marketId);
        $this->syncBlockCounts($blockId);
    }

    public function assignChief(Place $place, User $user): Place
    {
        PlaceMember::query()
            ->where('place_id', $place->id)
            ->where('role', PlaceMemberRole::Chief)
            ->delete();

        PlaceMember::updateOrCreate(
            ['place_id' => $place->id, 'user_id' => $user->id],
            ['role' => PlaceMemberRole::Chief]
        );

        $place->update([
            'chief_user_id' => $user->id,
            'status' => PlaceStatus::Occupied,
        ]);

        $this->syncMarketCounts($place->market_id);
        $this->activityLog->log('place.chief_assigned', $place, ['user_id' => $user->id]);

        return $place->fresh(['market', 'block', 'chief', 'members.user']);
    }

    public function addMember(Place $place, User $user): PlaceMember
    {
        $member = PlaceMember::firstOrCreate(
            ['place_id' => $place->id, 'user_id' => $user->id],
            ['role' => PlaceMemberRole::Member]
        );

        $this->activityLog->log('place.member_added', $place, ['user_id' => $user->id]);

        return $member->load('user');
    }

    private function syncMarketCounts(int $marketId): void
    {
        $total = Place::where('market_id', $marketId)->count();
        $occupied = Place::where('market_id', $marketId)
            ->whereIn('status', [PlaceStatus::Occupied, PlaceStatus::Reserved])
            ->count();

        \App\Models\Market::where('id', $marketId)->update([
            'total_places' => $total,
            'occupied_places' => $occupied,
        ]);
    }

    private function syncBlockCounts(?int $blockId): void
    {
        if (! $blockId) {
            return;
        }

        $block = \App\Models\MarketBlock::find($blockId);
        if ($block) {
            $this->blockService->syncPlacesCount($block);
        }
    }
}