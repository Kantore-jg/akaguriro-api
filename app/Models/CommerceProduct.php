<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommerceProduct extends Model
{
    protected $fillable = [
        'commerce_id',
        'category_id',
        'name',
        'sku',
        'barcode',
        'unit',
        'purchase_price',
        'sale_price',
        'min_stock',
        'max_stock',
        'vat_rate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'min_stock' => 'decimal:3',
            'max_stock' => 'decimal:3',
            'vat_rate' => 'decimal:2',
        ];
    }

    public function commerce(): BelongsTo
    {
        return $this->belongsTo(Commerce::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CommerceProductCategory::class, 'category_id');
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class, 'product_id');
    }
}
