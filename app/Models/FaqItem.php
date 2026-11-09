<?php

namespace App\Models;

use App\Models\Concerns\HasVisibility;
use App\Models\Concerns\Sortable;
use App\Models\Concerns\TriggersPublish;
use Illuminate\Database\Eloquent\Model;

class FaqItem extends Model
{
    use HasVisibility, Sortable, TriggersPublish;

    protected $table = 'faq_items';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }
}
