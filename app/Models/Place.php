<?php

namespace App\Models;

use App\Enums\PlaceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Place extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'market_id', 'market_block_id', 'number', 'qr_code', 'status',
        'category', 'latitude', 'longitude', 'chief_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlaceStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(MarketBlock::class, 'market_block_id');
    }

    public function chief(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chief_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(PlaceMember::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}