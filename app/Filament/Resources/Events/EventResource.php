<?php

namespace App\Filament\Resources\Events;

use App\Filament\Forms\ImageField;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Support\ContentResource;
use App\Models\Event;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EventResource extends ContentResource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'событие';

    protected static ?string $pluralModelLabel = 'Лента событий';

    protected static ?int $navigationSort = 2;

    public static function siteUrl(Model $record): string
    {
        return route('events.show', $record->slug);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ...static::titleWithSlug(),
            DatePicker::make('date')->label('Дата')->required(),
            TextInput::make('client')->label('Заказчик')->required()->maxLength(255),
            static::rich('description', 'Описание'),
            Toggle::make('demo')->label('Демо-пример (не реальное событие)'),
            ImageField::make('photos')->label('Фото')->aspect(4 / 3)->focal()->multiple(6),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return static::contentTable($table, [
            TextColumn::make('title')->label('Название')->searchable(),
            TextColumn::make('date')->label('Дата')->date('d.m.Y'),
            TextColumn::make('client')->label('Заказчик'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
