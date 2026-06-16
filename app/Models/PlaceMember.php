<?php

namespace App\Models;

use App\Enums\PlaceMemberRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceMember extends Model
{
    protected $fillable = ['place_id', 'user_id', 'role'];

    protected function casts(): array
    {
        return ['role' => PlaceMemberRole::class];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}