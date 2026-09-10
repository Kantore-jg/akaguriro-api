<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommerceProductCategory extends Model
{
    protected $fillable = [
        'commerce_id',
        'name',
        'slug',
    ];

    public function commerce(): BelongsTo
    {
        return $this->belongsTo(Commerce::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(CommerceProduct::class, 'category_id');
    }
}
