<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $fillable = [
        'commerce_id',
        'name',
        'status',
    ];

    public function commerce(): BelongsTo
    {
        return $this->belongsTo(Commerce::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    public function currentOpenSession(): ?CashSession
    {
        return $this->sessions()->where('status', 'open')->latest('opened_at')->first();
    }
}
