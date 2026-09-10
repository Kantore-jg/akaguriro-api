<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashMovement extends Model
{
    protected $fillable = [
        'cash_session_id',
        'type',
        'amount',
        'motif',
        'reference_type',
        'reference_id',
        'user_id',
        'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'moved_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
