<?php

namespace App\Models;

use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasVisibility;
use App\Models\Concerns\Sortable;
use App\Models\Concerns\TriggersPublish;
use App\Support\CaseDate;
use Database\Factories\CaseStudyFactory;
use Illuminate\Database\Eloquent\Builder;
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
        return ['plan' => 'array', 'demo' => 'boolean', 'is_visible' => 'boolean', 'held_at' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (CaseStudy $case) {
            // Нет даты — пробуем понять её из подписи; нет подписи — составляем её из даты.
            if (! $case->held_at && filled($case->date_label)) {
                $case->held_at = CaseDate::parse($case->date_label);
            }
            if (blank($case->date_label) && $case->held_at) {
                $case->date_label = CaseDate::label($case->held_at);
            }
        });
    }

    /** От новых к старым; без даты — в конце; при равных датах — ручной порядок. */
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByRaw('held_at is null')->orderByDesc('held_at')->orderBy('sort')->orderBy('id');
    }
}
