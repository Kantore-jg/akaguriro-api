<?php

namespace App\Services;

use App\Enums\ReceiptStatus;
use App\Models\PaymentReceipt;
use App\Models\User;
use App\Notifications\ReceiptReviewedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class PaymentReceiptService
{
    public function __construct(
        private FileStorageService $fileStorage,
        private ActivityLogService $activityLog,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PaymentReceipt::query()->with(['user', 'market', 'place', 'reviewer']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['market_id'])) {
            $query->where('market_id', $filters['market_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(User $user, array $data, UploadedFile $file): PaymentReceipt
    {
        $receipt = PaymentReceipt::create([
            ...$data,
            'user_id' => $user->id,
            'file_path' => $this->fileStorage->store($file, 'receipts'),
            'status' => ReceiptStatus::Pending,
            'history' => [[
                'action' => 'submitted',
                'at' => now()->toIso8601String(),
                'by' => $user->id,
            ]],
        ]);

        $this->activityLog->log('receipt.submitted', $receipt);

        return $receipt->load(['user', 'market', 'place']);
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

        return $receipt->fresh(['user', 'market', 'place', 'reviewer']);
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

        return $receipt->fresh(['user', 'market', 'place', 'reviewer']);
    }
}