<?php

namespace App\Models;

use App\Enums\CommerceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commerce extends Model
{
    protected $fillable = [
        'name',
        'type',
        'rccm',
        'nif',
        'phone',
        'email',
        'address',
        'province',
        'commune',
        'zone',
        'colline',
        'description',
        'logo_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommerceStatus::class,
        ];
    }

    public function commerceUsers(): HasMany
    {
        return $this->hasMany(CommerceUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'commerce_users')
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(CommerceProductCategory::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(CommerceProduct::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(CommercePurchase::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(CommerceSale::class);
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class);
    }
}
