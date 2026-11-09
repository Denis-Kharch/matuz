<?php

namespace App\Filament\Resources\GalleryItems;

use App\Filament\Forms\ImageField;
use App\Filament\Resources\GalleryItems\Pages\CreateGalleryItem;
use App\Filament\Resources\GalleryItems\Pages\EditGalleryItem;
use App\Filament\Resources\GalleryItems\Pages\ListGalleryItems;
use App\Filament\Support\ContentResource;
use App\Models\GalleryItem;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GalleryItemResource extends ContentResource
{
    protected static ?string $model = GalleryItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $modelLabel = 'фото галереи';

    protected static ?string $pluralModelLabel = 'Галерея';

    protected static ?int $navigationSort = 5;

    public static function siteUrl(Model $record): string
    {
        return static::anchor('gallery');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('caption')->label('Подпись')->required()->maxLength(255),
            ImageField::make('image')->label('Фото'),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return static::contentTable($table, [
            TextColumn::make('caption')->label('Подпись')->searchable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGalleryItems::route('/'),
            'create' => CreateGalleryItem::route('/create'),
            'edit' => EditGalleryItem::route('/{record}/edit'),
        ];
    }
}
