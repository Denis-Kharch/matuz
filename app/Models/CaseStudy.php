<?php

namespace App\Models;

use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasVisibility;
use App\Models\Concerns\Sortable;
use App\Models\Concerns\TriggersPublish;
use Database\Factories\CaseStudyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseStudy extends Model
{
    /** @use HasFactory<CaseStudyFactory> */
    use HasFactory, HasMedia, HasVisibility, Sortable, TriggersPublish;

    protected $table = 'case_studies';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['plan' => 'array', 'demo' => 'boolean', 'is_visible' => 'boolean'];
    }
}
