<?php

namespace App\Filament\Forms;

use App\Models\Media;
use App\Support\ImageManifest;
use Filament\Forms\Components\Field;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Превью фото элемента ImageField с точкой фокуса и окном кадрирования (Cropper.js).
 * Состояние — кадр {x,y,width,height,rotate,flipX,flipY} или null; фокус пишет в соседние focal_x/focal_y.
 */
class CropEditor extends Field
{
    protected string $view = 'filament.forms.crop-field';

    protected ?ImageField $imageField = null;

    public function imageField(ImageField $field): static
    {
        $this->imageField = $field;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getEditorConfig(): array
    {
        $get = $this->makeGetUtility();
        $file = collect(Arr::wrap($get('upload')))->first(fn ($f) => $f instanceof UploadedFile);
        $media = $file ? null : $this->media($get('id'));

        $source = match (true) {
            $file instanceof TemporaryUploadedFile => $this->temporaryUrl($file),
            $media !== null => route('filament.admin.media.original', $media),
            default => null,
        };
        $preview = $media?->key ? ImageManifest::url($media->key, 'webp', 640) : null;
        $item = $this->getContainer()->getStatePath();

        return [
            'sourceUrl' => $source,
            'previewUrl' => $preview ?? $source,
            'savedCrop' => $media?->crop,
            'isNew' => $file !== null,
            'focalXPath' => "{$item}.focal_x",
            'focalYPath' => "{$item}.focal_y",
            'uploadPath' => "{$item}.upload",
            'aspect' => $this->imageField?->getAspect(),
            'focal' => (bool) $this->imageField?->hasFocal(),
            'contain' => (bool) $this->imageField?->isContain(),
        ];
    }

    private function media(mixed $id): ?Media
    {
        if (! is_numeric($id)) {
            return null;
        }

        return $this->imageField?->sync()?->query()->find((int) $id);
    }

    private function temporaryUrl(TemporaryUploadedFile $file): ?string
    {
        try {
            return $file->temporaryUrl();
        } catch (Throwable) {
            return null; // не превьюируемый тип — ошибку покажет проверка файла
        }
    }
}
