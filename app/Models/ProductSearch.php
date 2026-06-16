<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSearch extends Model
{
    protected $fillable = ['query', 'product_id', 'market_id', 'user_id', 'ip_address'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}