<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommercePurchase extends Model
{
    protected $fillable = [
        'commerce_id',
        'stock_id',
        'supplier_name',
        'purchase_date',
        'payment_status',
        'paid_from_cash',
        'cash_register_id',
        'cash_session_id',
        'total_amount',
        'user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'paid_from_cash' => 'boolean',
            'total_amount' => 'decimal:2',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CommercePurchaseItem::class, 'purchase_id');
    }
}
