<?php

namespace App\Models;

use App\Models\Concerns\HasVisibility;
use App\Models\Concerns\Sortable;
use App\Models\Concerns\TriggersPublish;
use Illuminate\Database\Eloquent\Model;

class Format extends Model
{
    use HasVisibility, Sortable, TriggersPublish;

    protected $table = 'formats';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['tags' => 'array', 'is_visible' => 'boolean'];
    }
}
