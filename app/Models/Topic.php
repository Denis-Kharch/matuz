<?php

namespace App\Models;

use App\Models\Concerns\HasVisibility;
use App\Models\Concerns\Sortable;
use App\Models\Concerns\TriggersPublish;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Topic extends Model
{
    use HasVisibility, Sortable, TriggersPublish;

    protected $table = 'topics';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TopicGroup::class, 'topic_group_id');
    }
}
