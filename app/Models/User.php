<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected string $guard_name = 'sanctum';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'is_active',
        'managed_market_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function managedMarket(): BelongsTo
    {
        return $this->belongsTo(Market::class, 'managed_market_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function placeMembers(): HasMany
    {
        return $this->hasMany(PlaceMember::class);
    }

    public function placeRequests(): HasMany
    {
        return $this->hasMany(PlaceRequest::class);
    }

    public function paymentReceipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    public function chiefPlaces(): HasMany
    {
        return $this->hasMany(Place::class, 'chief_user_id');
    }
}