<?php

namespace App\Filament\Forms;

use App\Media\MediaSync;
use App\Models\Media;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;

/**
 * Фото с кадрированием: список записей Media владельца (коллекция = имя поля)
 * или одиночное фото настроек без владельца (ImageField::slot('hero')).
 *
 *   ImageField::make('cover')->aspect(16 / 10)->focal()
 *   ImageField::make('photos')->aspect(4 / 3)->multiple(3)
 *   ImageField::make('logo')->contain()
 *   ImageField::slot('hero')->aspect(16 / 9)->focal()
 *
 * Элемент: превью и «Кадрировать» (CropEditor), «Заменить», подпись, alt; порядок — перетаскиванием.
 * Сохраняется вместе с формой (getState / saveRelationships) через MediaSync; нарезку делает Media.
 */
class ImageField extends Repeater
{
    protected ?string $slot = null;

    protected ?float $aspect = null;

    protected bool $hasFocal = false;

    protected bool $isContain = false;

    protected int $maxPhotos = 1;

    public static function slot(string $slot): static
    {
        $field = static::make($slot);
        $field->slot = $slot;

        return $field;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultItems(0)
            ->dehydrated(false)
            ->extraAttributes(fn (): array => ['data-image-field' => $this->getName()])
            ->maxItems(fn (): int => $this->maxPhotos)
            ->reorderable(fn (): bool => $this->isMultiple())
            ->reorderableWithDragAndDrop(fn (): bool => $this->isMultiple())
            ->reorderableWithButtons(false)
            ->grid(fn (): int => $this->isMultiple() ? 2 : 1)
            ->itemLabel(fn (array $state): string => filled($state['caption'] ?? null) ? Str::limit($state['caption'], 40) : 'Фото')
            ->addActionLabel(fn (): string => $this->isMultiple() ? 'Добавить фото' : 'Загрузить фото')
            ->addAction(fn (Action $action): Action => $this->configureAddAction($action))
            ->deleteAction(fn (Action $action): Action => $action->requiresConfirmation()->modalHeading('Удалить фото?'))
            ->extraItemActions([$this->replaceAction()])
            ->schema(fn (): array => $this->itemSchema())
            ->loadStateFromRelationshipsUsing(fn (ImageField $component) => $component->state($component->sync()?->items() ?? []))
            ->saveRelationshipsUsing(fn (ImageField $component) => $component->persist());
    }

    public function aspect(?float $ratio): static
    {
        $this->aspect = $ratio;

        return $this;
    }

    public function focal(bool $condition = true): static
    {
        $this->hasFocal = $condition;

        return $this;
    }

    public function multiple(int $max = 30): static
    {
        $this->maxPhotos = max(1, $max);

        return $this;
    }

    /** Логотип: без кадрирования и фокуса, прозрачность PNG/WebP сохраняется. */
    public function contain(bool $condition = true): static
    {
        $this->isContain = $condition;

        return $this;
    }

    public function getAspect(): ?float
    {
        return $this->aspect;
    }

    public function hasFocal(): bool
    {
        return $this->hasFocal && ! $this->isContain;
    }

    public function isContain(): bool
    {
        return $this->isContain;
    }

    public function isMultiple(): bool
    {
        return $this->maxPhotos > 1;
    }

    public function getSlot(): ?string
    {
        return $this->slot;
    }

    /** Источник записей: владелец формы (коллекция = имя поля) или slot; null — у формы ещё нет записи. */
    public function sync(): ?MediaSync
    {
        if ($this->slot !== null) {
            return MediaSync::forSlot($this->slot);
        }
        $record = $this->getRecord();

        return $record instanceof Model && $record->exists ? MediaSync::forOwner($record, $this->getName()) : null;
    }

    public function persist(): void
    {
        $this->sync()?->save(
            $this->getRawState() ?? [],
            withCrop: ! $this->isContain,
            errorKey: fn ($key) => "{$this->getStatePath()}.{$key}.upload",
            // Сразу после фиксации фото форма знает его id и больше не держит загрузку:
            // повторное сохранение (и повтор после сбоя на следующем фото) не создаст дубль.
            saved: function ($key, Media $media): void {
                $items = $this->getRawState() ?? [];
                if (isset($items[$key])) {
                    $items[$key] = [...$items[$key], 'id' => $media->id, 'upload' => null, 'crop' => $media->crop];
                    $this->rawState($items);
                }
            },
        );
    }

    /** Слоты настроек не привязаны к записи формы: загружаются и сохраняются и без неё. */
    public function loadStateFromRelationships(bool $shouldHydrate = false): void
    {
        if ($this->slot === null || $this->getRecord()?->exists) {
            parent::loadStateFromRelationships($shouldHydrate);

            return;
        }
        $this->state(MediaSync::forSlot($this->slot)->items());
        if ($shouldHydrate) {
            $this->callAfterStateHydrated();
            foreach ($this->getChildSchemas(withHidden: true) as $childSchema) {
                $childSchema->callAfterStateHydrated();
            }
        }
    }

    public function saveRelationships(): void
    {
        if ($this->slot === null || $this->getRecord()?->exists) {
            parent::saveRelationships();

            return;
        }
        if (! $this->isHidden() && ! $this->isDisabled()) {
            $this->persist();
        }
    }

    /** @return array<Component> */
    protected function itemSchema(): array
    {
        return [
            Hidden::make('id'),
            Hidden::make('upload')
                ->rule(fn () => new ImageUploadRule($this->isContain))
                ->live()
                ->afterStateUpdated(function (mixed $state, Set $set, Hidden $component, Livewire $livewire) {
                    // Livewire дописывает загрузку к массиву — оставляем только последний файл.
                    $files = array_values(array_filter(Arr::wrap($state), fn ($f) => $f instanceof UploadedFile));
                    $component->state($files ? end($files) : null);
                    // Новый оригинал — прежний кадр и фокус к нему не относятся.
                    $set('crop', null);
                    $set('focal_x', 50);
                    $set('focal_y', 50);
                    $livewire->resetErrorBag($component->getStatePath());
                    $livewire->validateOnly($component->getStatePath());
                }),
            Hidden::make('focal_x')->default(50),
            Hidden::make('focal_y')->default(50),
            CropEditor::make('crop')->label('Кадр')->hiddenLabel()->imageField($this),
            TextInput::make('caption')->label('Подпись')->maxLength(255)->hidden(fn (): bool => $this->isContain),
            TextInput::make('alt')->label('Описание для незрячих (alt)')->maxLength(255),
        ];
    }

    protected function uploadInput(string $name, bool $multiple): FileUpload
    {
        return FileUpload::make($name)
            ->label($multiple ? 'Фото (можно несколько сразу)' : 'Фото')
            ->helperText($this->isContain
                ? 'PNG или WebP с прозрачным фоном, до 15 МБ.'
                : 'JPG, PNG или WebP, до 15 МБ. HEIC с iPhone сохраните в JPG.')
            ->multiple($multiple)
            ->maxFiles(fn (): int => $multiple ? max(1, $this->maxPhotos - count($this->getRawState() ?? [])) : 1)
            ->storeFiles(false)
            ->previewable(false)
            ->required()
            ->rule(new ImageUploadRule($this->isContain));
    }

    protected function configureAddAction(Action $action): Action
    {
        return $action
            ->modalHeading(fn (): string => $this->isMultiple() ? 'Добавить фото' : 'Загрузить фото')
            ->modalSubmitActionLabel('Добавить')
            ->schema(fn (): array => [$this->uploadInput('files', $this->isMultiple())])
            ->action(function (array $data, ImageField $component): void {
                $items = $component->getRawState() ?? [];
                foreach (array_slice(Arr::wrap($data['files'] ?? []), 0, max(0, $component->maxPhotos - count($items))) as $file) {
                    $items[(string) Str::uuid()] = [
                        'id' => null, 'upload' => $file, 'crop' => null,
                        'focal_x' => 50, 'focal_y' => 50, 'caption' => null, 'alt' => null,
                    ];
                }
                $component->rawState($items);
                $component->callAfterStateUpdated();
            });
    }

    protected function replaceAction(): Action
    {
        return Action::make('replace')
            ->label('Заменить')
            ->icon(Heroicon::OutlinedArrowPath)
            ->iconButton()
            ->color('gray')
            ->modalHeading('Заменить фото')
            ->modalDescription('Подпись и alt сохранятся, кадр и фокус сбросятся.')
            ->modalSubmitActionLabel('Заменить')
            ->schema(fn (): array => [$this->uploadInput('file', false)])
            ->action(function (array $arguments, array $data, ImageField $component): void {
                $items = $component->getRawState() ?? [];
                $key = $arguments['item'] ?? null;
                $file = Arr::first(Arr::wrap($data['file'] ?? null));
                if (! isset($items[$key]) || ! $file) {
                    return;
                }
                $items[$key] = [...$items[$key], 'upload' => $file, 'crop' => null, 'focal_x' => 50, 'focal_y' => 50];
                $component->rawState($items);
                $component->callAfterStateUpdated();
            });
    }
}
