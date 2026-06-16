<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedDisplay extends Model
{
    protected $fillable = [
        'market_id', 'display_type', 'payload', 'refresh_interval',
        'is_active', 'last_refreshed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_active' => 'boolean',
            'last_refreshed_at' => 'datetime',
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}