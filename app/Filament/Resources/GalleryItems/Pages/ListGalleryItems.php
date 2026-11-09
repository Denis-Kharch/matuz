<?php

namespace App\Filament\Resources\GalleryItems\Pages;

use App\Filament\Resources\GalleryItems\GalleryItemResource;
use App\Media\MediaProcessingFailed;
use App\Media\MediaSync;
use App\Models\GalleryItem;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ListGalleryItems extends ListRecords
{
    protected static string $resource = GalleryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkUpload')
                ->label('Загрузить пачкой')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->modalHeading('Загрузить несколько фото')
                ->modalDescription('Каждое фото станет отдельным элементом галереи. Подписи можно добавить после.')
                ->modalSubmitActionLabel('Загрузить')
                ->schema([
                    FileUpload::make('files')
                        ->label('Фото (до 30 штук)')
                        ->multiple()
                        ->maxFiles(30)
                        ->storeFiles(false)
                        ->previewable(false)
                        ->required()
                        ->rules([new \App\Filament\Forms\ImageUploadRule]),
                ])
                ->action(function (array $data): void {
                    foreach (array_slice(Arr::wrap($data['files'] ?? []), 0, 30) as $file) {
                        $item = GalleryItem::create(['caption' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)]);
                        try {
                            MediaSync::forOwner($item, 'image')->save(
                                [['id' => null, 'upload' => $file]],
                                withCrop: true,
                                errorKey: fn () => 'files',
                            );
                        } catch (ValidationException|MediaProcessingFailed $e) {
                            $item->delete(); // не оставляем элемент без фото
                            throw $e;
                        }
                    }
                }),
            CreateAction::make(),
        ];
    }
}
