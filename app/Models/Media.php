<?php

namespace App\Models;

use App\Media\MediaProcessor;
use App\Models\Concerns\Sortable;
use App\Models\Concerns\TriggersPublish;
use App\Support\ImageManifest;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * Фото: оригинал на диске local (media-originals/…), кадр/поворот/фокус и
 * нарезанные варианты {key}-{w}.{webp|avif} в папке сайта.
 */
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory, Sortable, TriggersPublish;

    protected $table = 'media';

    protected $guarded = [];

    /** Нужна ли нарезка после текущего сохранения (оригинал или кадр изменились). */
    private bool $needsProcessing = false;

    protected function casts(): array
    {
        return [
            'crop' => 'array',
            'widths' => 'array',
            'formats' => 'array',
            'focal_x' => 'integer',
            'focal_y' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Media $m) {
            $m->needsProcessing = $m->isDirty(['original_path', 'crop']);
        });
        static::saved(function (Media $m) {
            if ($m->needsProcessing) {
                $m->needsProcessing = false;
                app(MediaProcessor::class)->process($m);
            }
            ImageManifest::flush();
        });
        static::deleted(function (Media $m) {
            app(MediaProcessor::class)->deleteVariants($m);
            Storage::disk('local')->delete($m->original_path);
            ImageManifest::flush();
        });
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /** Тестовая фабрика: обложка 3000×2000 с кадром 1600×1000. */
    public static function factoryCover(array $attributes = []): self
    {
        return static::factory()->cover()->create($attributes)->refresh();
    }
}
