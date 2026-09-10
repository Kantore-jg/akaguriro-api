<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommerceSale extends Model
{
    protected $fillable = [
        'commerce_id',
        'stock_id',
        'cash_session_id',
        'payment_method',
        'total_amount',
        'total_cost',
        'user_id',
        'sold_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }

    public function commerce(): BelongsTo
    {
        return $this->belongsTo(Commerce::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CommerceSaleItem::class, 'sale_id');
    }
}
