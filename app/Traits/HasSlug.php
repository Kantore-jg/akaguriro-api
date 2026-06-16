<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug) && ! empty($model->name)) {
                $model->slug = static::generateUniqueSlug($model->name, $model);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->getOriginal('slug'))) {
                $model->slug = static::generateUniqueSlug($model->name, $model);
            }
        });
    }

    protected static function generateUniqueSlug(string $name, $model): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($model->exists, fn ($q) => $q->where('id', '!=', $model->id))
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}