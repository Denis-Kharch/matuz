<?php

namespace App\Media;

use App\Models\Media;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Список фото владельца (коллекция) или одиночного блока настроек (slot) ↔ состояние формы.
 * Элемент формы: id (записи Media или null), upload (новый файл или null), crop, focal_x, focal_y, caption, alt.
 */
final class MediaSync
{
    private const EXT = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

    private function __construct(private ?Model $owner, private string $collection, private ?string $slot) {}

    public static function forOwner(Model $owner, string $collection): self
    {
        return new self($owner, $collection, null);
    }

    /** Одиночное фото настроек: без владельца, коллекция default (как в ContentImporter). */
    public static function forSlot(string $slot): self
    {
        return new self(null, 'default', $slot);
    }

    /** @return Builder<Media> */
    public function query(): Builder
    {
        $q = Media::query()->where('collection', $this->collection);
        $q = $this->owner
            ? $q->where('owner_type', $this->owner->getMorphClass())->where('owner_id', $this->owner->getKey())
            : $q->whereNull('owner_type')->where('slot', $this->slot);

        return $q->orderBy('sort')->orderBy('id');
    }

    /** @return list<array<string, mixed>> */
    public function items(): array
    {
        return $this->query()->get()->map(fn (Media $m) => [
            'id' => $m->id,
            'upload' => null,
            'crop' => $m->crop,
            'focal_x' => $m->focal_x,
            'focal_y' => $m->focal_y,
            'caption' => $m->caption,
            'alt' => $m->alt,
        ])->all();
    }

    /**
     * Создать/обновить/удалить записи Media по списку; порядок = порядок элементов.
     *
     * @param  array<string|int, array<string, mixed>>  $items
     * @param  Closure(string|int): string  $errorKey  ключ ошибки валидации для элемента
     *
     * @throws ValidationException если фото не удалось обработать
     */
    public function save(array $items, bool $withCrop, Closure $errorKey): void
    {
        $existing = $this->query()->get()->keyBy('id');
        $kept = [];
        $sort = 0;

        foreach ($items as $key => $item) {
            $media = $existing->get((int) ($item['id'] ?? 0));
            $file = collect(Arr::wrap($item['upload'] ?? null))->first(fn ($f) => $f instanceof UploadedFile);
            if (! $media && ! $file) {
                continue; // пустой элемент без фото
            }

            $media ??= new Media($this->owner
                ? ['owner_type' => $this->owner->getMorphClass(), 'owner_id' => $this->owner->getKey(), 'collection' => $this->collection]
                : ['collection' => $this->collection, 'slot' => $this->slot]);
            $oldOriginal = $file ? $media->original_path : null;

            $media->fill([
                'crop' => $withCrop ? self::crop($item['crop'] ?? null) : null,
                'focal_x' => self::percent($item['focal_x'] ?? 50),
                'focal_y' => self::percent($item['focal_y'] ?? 50),
                'caption' => self::text($item['caption'] ?? null),
                'alt' => self::text($item['alt'] ?? null),
                'sort' => ++$sort,
            ]);
            if ($file) {
                $media->original_path = $this->storeOriginal($file);
            }

            try {
                $media->save();
            } catch (MediaProcessingFailed $e) {
                throw ValidationException::withMessages([$errorKey($key) => $e->getMessage()]);
            }

            if ($oldOriginal && $oldOriginal !== $media->original_path) {
                Storage::disk('local')->delete($oldOriginal);
            }
            $kept[] = $media->id;
        }

        $existing->except($kept)->each->delete();
    }

    private function storeOriginal(UploadedFile $file): string
    {
        $type = @getimagesize($file->getRealPath())[2] ?? null;
        $name = bin2hex(random_bytes(8)).'.'.(self::EXT[$type] ?? 'jpg');
        $path = Storage::disk('local')->putFileAs('media-originals', $file, $name);
        if ($file instanceof TemporaryUploadedFile) {
            $file->delete();
        }

        return $path;
    }

    /** Кадр Cropper.js → {x,y,width,height,rotate,flipX,flipY} целыми; null — весь кадр. */
    public static function crop(mixed $crop): ?array
    {
        if (! is_array($crop)) {
            return null;
        }
        $int = fn (string $k) => is_numeric($crop[$k] ?? null) ? (int) round((float) $crop[$k]) : 0;
        if ($int('width') < 1 || $int('height') < 1) {
            return null;
        }

        return [
            'x' => max(0, $int('x')),
            'y' => max(0, $int('y')),
            'width' => $int('width'),
            'height' => $int('height'),
            'rotate' => (($int('rotate') % 360) + 360) % 360,
            'flipX' => filter_var($crop['flipX'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'flipY' => filter_var($crop['flipY'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    private static function percent(mixed $v): int
    {
        return is_numeric($v) ? max(0, min(100, (int) round((float) $v))) : 50;
    }

    private static function text(mixed $v): ?string
    {
        $v = is_string($v) ? trim($v) : null;

        return $v === '' ? null : $v;
    }
}
