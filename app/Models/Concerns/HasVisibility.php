<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasVisibility
{
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }
}
