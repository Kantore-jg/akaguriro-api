<?php

namespace App\Services;

use App\Enums\PlaceStatus;
use App\Enums\ReceiptStatus;
use App\Models\PaymentMethod;
use App\Models\PaymentReceipt;
use App\Models\Place;
use App\Models\User;
use App\Notifications\ReceiptReviewedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class PaymentReceiptService
{
    public function __construct(
        private FileStorageService $fileStorage,
        private ActivityLogService $activityLog,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PaymentReceipt::query()->with([
            'user', 'market', 'place', 'reviewer', 'paymentMethod',
        ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['market_id'])) {
            $query->where('market_id', $filters['market_id']);
        }

        if (! empty($filters['period_year'])) {
            $query->where('period_year', $filters['period_year']);
        }

        if (! empty($filters['period_month'])) {
            $query->where('period_month', $filters['period_month']);
        }

        if (! empty($filters['payment_method_id'])) {
            $query->where('payment_method_id', $filters['payment_method_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(User $user, array $data, UploadedFile $file): PaymentReceipt
    {
        $place = Place::query()
            ->with('block')
            ->findOrFail($data['place_id']);

        if ($place->status !== PlaceStatus::Occupied) {
            throw ValidationException::withMessages([
                'place_id' => ['Le loyer ne peut être déclaré que pour une place occupée.'],
            ]);
        }

        if ((int) $place->chief_user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'place_id' => ['Cette place ne vous est pas assignée.'],
            ]);
        }

        $year = (int) $data['period_year'];
        $month = (int) $data['period_month'];
        $expectedAmount = (int) ($place->block?->rent_amount ?? 0);

        if ($expectedAmount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Le loyer du bloc n\'est pas configuré.'],
            ]);
        }

        $method = PaymentMethod::query()->findOrFail($data['payment_method_id']);
        if (! $method->is_active || (int) $method->market_id !== (int) $place->market_id) {
            throw ValidationException::withMessages([
                'payment_method_id' => ['Moyen de paiement invalide pour ce marché.'],
            ]);
        }

        $duplicate = PaymentReceipt::query()
            ->where('place_id', $place->id)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->whereIn('status', [ReceiptStatus::Pending->value, ReceiptStatus::Approved->value])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'period_month' => ['Un paiement est déjà enregistré pour cette place et cette période.'],
            ]);
        }

        $receipt = PaymentReceipt::create([
            'user_id' => $user->id,
            'market_id' => $place->market_id,
            'place_id' => $place->id,
            'period_year' => $year,
            'period_month' => $month,
            'payment_method_id' => $method->id,
            'amount' => $expectedAmount,
            'reference' => sprintf('%04d-%02d', $year, $month),
            'file_path' => $this->fileStorage->store($file, 'receipts'),
            'status' => ReceiptStatus::Pending,
            'history' => [[
                'action' => 'submitted',
                'at' => now()->toIso8601String(),
                'by' => $user->id,
            ]],
        ]);

        $this->activityLog->log('receipt.submitted', $receipt);

        return $receipt->load(['user', 'market', 'place', 'paymentMethod']);
    }

    public function approve(PaymentReceipt $receipt, User $reviewer): PaymentReceipt
    {
        $history = $receipt->history ?? [];
        $history[] = ['action' => 'approved', 'at' => now()->toIso8601String(), 'by' => $reviewer->id];

        $receipt->update([
            'status' => ReceiptStatus::Approved,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'history' => $history,
        ]);

        $receipt->user->notify(new ReceiptReviewedNotification($receipt, 'approved'));
        $this->activityLog->log('receipt.approved', $receipt);

        return $receipt->fresh(['user', 'market', 'place', 'reviewer', 'paymentMethod']);
    }

    public function reject(PaymentReceipt $receipt, User $reviewer, string $reason): PaymentReceipt
    {
        $history = $receipt->history ?? [];
        $history[] = ['action' => 'rejected', 'at' => now()->toIso8601String(), 'by' => $reviewer->id, 'reason' => $reason];

        $receipt->update([
            'status' => ReceiptStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'history' => $history,
        ]);

        $receipt->user->notify(new ReceiptReviewedNotification($receipt, 'rejected'));
        $this->activityLog->log('receipt.rejected', $receipt);

        return $receipt->fresh(['user', 'market', 'place', 'reviewer', 'paymentMethod']);
    }
}
