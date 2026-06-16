<?php

namespace App\Services;

use App\Enums\PlaceRequestStatus;
use App\Enums\PlaceStatus;
use App\Enums\UserRole;
use App\Models\Place;
use App\Models\PlaceRequest;
use App\Models\User;
use App\Notifications\PlaceRequestReviewedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceRequestService
{
    public function __construct(
        private PlaceService $placeService,
        private ActivityLogService $activityLog,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PlaceRequest::query()->with(['user', 'market', 'place', 'reviewer']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['market_id'])) {
            $query->where('market_id', $filters['market_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(User $user, array $data): PlaceRequest
    {
        $request = PlaceRequest::create([
            ...$data,
            'user_id' => $user->id,
            'status' => PlaceRequestStatus::Pending,
            'history' => [[
                'action' => 'created',
                'at' => now()->toIso8601String(),
                'by' => $user->id,
            ]],
        ]);

        $this->activityLog->log('place_request.created', $request);

        return $request->load(['user', 'market']);
    }

    public function approve(PlaceRequest $request, User $reviewer, ?int $placeId = null): PlaceRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $placeId) {
            $place = $this->resolvePlace($request, $placeId);

            $history = $request->history ?? [];
            $history[] = [
                'action' => 'approved',
                'at' => now()->toIso8601String(),
                'by' => $reviewer->id,
                'place_id' => $place->id,
            ];

            $request->update([
                'status' => PlaceRequestStatus::Assigned,
                'place_id' => $place->id,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'history' => $history,
            ]);

            $this->placeService->assignChief($place, $request->user);
            $request->user->syncRoles([UserRole::Commercant->value]);

            $request->user->notify(new PlaceRequestReviewedNotification($request, 'approved'));
            $this->activityLog->log('place_request.approved', $request);

            return $request->fresh(['user', 'market', 'place', 'reviewer']);
        });
    }

    public function reject(PlaceRequest $request, User $reviewer, string $reason): PlaceRequest
    {
        $history = $request->history ?? [];
        $history[] = [
            'action' => 'rejected',
            'at' => now()->toIso8601String(),
            'by' => $reviewer->id,
            'reason' => $reason,
        ];

        $request->update([
            'status' => PlaceRequestStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'history' => $history,
        ]);

        $request->user->notify(new PlaceRequestReviewedNotification($request, 'rejected'));
        $this->activityLog->log('place_request.rejected', $request);

        return $request->fresh(['user', 'market', 'reviewer']);
    }

    private function resolvePlace(PlaceRequest $request, ?int $placeId): Place
    {
        if ($placeId) {
            $place = Place::where('id', $placeId)
                ->where('market_id', $request->market_id)
                ->where('status', PlaceStatus::Available)
                ->first();

            if (! $place) {
                throw ValidationException::withMessages([
                    'place_id' => ['La place sélectionnée n\'est pas disponible.'],
                ]);
            }

            return $place;
        }

        $place = Place::where('market_id', $request->market_id)
            ->where('status', PlaceStatus::Available)
            ->first();

        if (! $place) {
            throw ValidationException::withMessages([
                'market_id' => ['Aucune place disponible dans ce marché.'],
            ]);
        }

        return $place;
    }
}