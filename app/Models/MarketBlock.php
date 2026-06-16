<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketBlock extends Model
{
    protected $fillable = [
        'market_id', 'name', 'code', 'description', 'total_places', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }
}