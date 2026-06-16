<?php

namespace App\Models;

use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasSlug, SoftDeletes;

    protected $fillable = [
        'user_id', 'market_id', 'place_id', 'category_id', 'name', 'slug',
        'description', 'price', 'unit', 'stock', 'available', 'is_trending',
        'view_count', 'search_count',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'available' => 'boolean',
            'is_trending' => 'boolean',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(ProductView::class);
    }
}