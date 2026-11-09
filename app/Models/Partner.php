<?php

namespace App\Models;

use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasVisibility;
use App\Models\Concerns\Sortable;
use App\Models\Concerns\TriggersPublish;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasMedia, HasVisibility, Sortable, TriggersPublish;

    protected $table = 'partners';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }
}
