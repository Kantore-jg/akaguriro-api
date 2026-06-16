<?php

namespace App\Models;

use App\Enums\ReceiptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceipt extends Model
{
    protected $fillable = [
        'user_id', 'market_id', 'place_id', 'file_path', 'amount', 'reference',
        'status', 'reviewed_by', 'reviewed_at', 'rejection_reason', 'history',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => ReceiptStatus::class,
            'reviewed_at' => 'datetime',
            'history' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}