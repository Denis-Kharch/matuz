<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait Sortable
{
    public static function bootSortable(): void
    {
        static::creating(function ($model) {
            if (($model->sort ?? 0) === 0) {
                $model->sort = ((int) static::query()->max('sort')) + 1;
            }
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }
}
