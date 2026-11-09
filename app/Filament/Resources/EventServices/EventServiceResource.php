<?php

namespace App\Filament\Resources\EventServices;

use App\Filament\Resources\EventServices\Pages\CreateEventService;
use App\Filament\Resources\EventServices\Pages\EditEventService;
use App\Filament\Resources\EventServices\Pages\ListEventServices;
use App\Filament\Support\ContentResource;
use App\Models\EventService;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EventServiceResource extends ContentResource
{
    protected static ?string $model = EventService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $modelLabel = 'мероприятие';

    protected static ?string $pluralModelLabel = 'Мероприятия';

    protected static ?int $navigationSort = 9;

    public static function siteUrl(Model $record): string
    {
        return static::anchor('directions');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Название')->required()->maxLength(255),
            Textarea::make('text')->label('Текст')->required()->rows(4),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return static::contentTable($table, [
            TextColumn::make('title')->label('Название')->searchable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventServices::route('/'),
            'create' => CreateEventService::route('/create'),
            'edit' => EditEventService::route('/{record}/edit'),
        ];
    }
}
