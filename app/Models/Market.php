<?php

namespace App\Models;

use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Market extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'city', 'location', 'description',
        'image', 'cover_image', 'total_places', 'occupied_places',
        'latitude', 'longitude', 'category_tags', 'is_active', 'visit_count',
    ];

    protected function casts(): array
    {
        return [
            'category_tags' => 'array',
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(MarketBlock::class);
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function ledDisplays(): HasMany
    {
        return $this->hasMany(LedDisplay::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(MarketVisit::class);
    }

    public function admins(): HasMany
    {
        return $this->hasMany(User::class, 'managed_market_id');
    }
}