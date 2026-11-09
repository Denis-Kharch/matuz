<?php

namespace App\Filament\Resources\Formats;

use App\Filament\Resources\Formats\Pages\CreateFormat;
use App\Filament\Resources\Formats\Pages\EditFormat;
use App\Filament\Resources\Formats\Pages\ListFormats;
use App\Filament\Support\ContentResource;
use App\Models\Format;
use BackedEnum;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FormatResource extends ContentResource
{
    protected static ?string $model = Format::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?string $modelLabel = 'формат обучения';

    protected static ?string $pluralModelLabel = 'Форматы обучения';

    protected static ?int $navigationSort = 7;

    public static function siteUrl(Model $record): string
    {
        return static::anchor('directions');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Название')->required()->maxLength(255),
            TagsInput::make('tags')->label('Метки')->required()->helperText('Введите метку и нажмите Enter.'),
            Textarea::make('description')->label('Описание')->required()->rows(4),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return static::contentTable($table, [
            TextColumn::make('title')->label('Название')->searchable(),
            TextColumn::make('tags')->label('Метки')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormats::route('/'),
            'create' => CreateFormat::route('/create'),
            'edit' => EditFormat::route('/{record}/edit'),
        ];
    }
}
