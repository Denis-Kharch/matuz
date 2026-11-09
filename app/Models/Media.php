<?php

namespace App\Models;

use App\Media\MediaProcessingFailed;
use App\Media\MediaProcessor;
use App\Models\Concerns\Sortable;
use App\Models\Concerns\TriggersPublish;
use App\Support\ImageManifest;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
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
                try {
                    app(MediaProcessor::class)->process($m);
                } catch (MediaProcessingFailed $e) {
                    // Строка откатится транзакцией save(); новый оригинал больше никому не нужен.
                    if ($m->isDirty('original_path')) {
                        Storage::disk('local')->delete($m->original_path);
                    }
                    throw $e;
                }
            }
            ImageManifest::flush();
        });
        static::deleted(function (Media $m) {
            app(MediaProcessor::class)->deleteVariants($m);
            Storage::disk('local')->delete($m->original_path);
            ImageManifest::flush();
        });
    }

    /** Сохранение и нарезка — одной транзакцией: сбой нарезки не оставляет полузаписанную строку. */
    public function save(array $options = []): bool
    {
        $wasNew = ! $this->exists;
        try {
            return DB::transaction(fn () => parent::save($options));
        } catch (MediaProcessingFailed $e) {
            if ($wasNew) {
                $this->exists = false;
                $this->wasRecentlyCreated = false;
                $this->setAttribute($this->getKeyName(), null);
            }
            throw $e;
        }
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
