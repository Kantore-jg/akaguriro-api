<?php

namespace App\Models;

use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'user_id', 'market_id', 'place_id', 'invoice_number',
        'client_name', 'client_phone', 'client_email',
        'payment_type', 'subtotal', 'total', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_type' => PaymentType::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
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

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}