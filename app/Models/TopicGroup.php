<?php

namespace App\Models;

use App\Models\Concerns\HasVisibility;
use App\Models\Concerns\Sortable;
use App\Models\Concerns\TriggersPublish;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TopicGroup extends Model
{
    use HasVisibility, Sortable, TriggersPublish;

    protected $table = 'topic_groups';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->ordered();
    }
}
