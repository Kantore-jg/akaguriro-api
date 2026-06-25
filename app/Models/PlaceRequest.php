<?php

namespace App\Models;

use App\Enums\PlaceRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceRequest extends Model
{
    protected $fillable = [
        'user_id', 'market_id', 'place_id', 'merchant_name', 'merchant_phone',
        'category', 'product_category_ids', 'description', 'status', 'reviewed_by', 'reviewed_at',
        'rejection_reason', 'history',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlaceRequestStatus::class,
            'reviewed_at' => 'datetime',
            'history' => 'array',
            'product_category_ids' => 'array',
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