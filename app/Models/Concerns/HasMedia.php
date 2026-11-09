<?php

namespace App\Models\Concerns;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMedia
{
    public function media(string $collection): MorphMany
    {
        return $this->morphMany(Media::class, 'owner')
            ->where('collection', $collection)
            ->orderBy('sort')
            ->orderBy('id');
    }

    public function firstMedia(string $collection): ?Media
    {
        return $this->media($collection)->first();
    }
}
