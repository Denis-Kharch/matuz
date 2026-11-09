<?php

namespace App\Filament\Resources\CaseStudies;

use App\Filament\Forms\ImageField;
use App\Filament\Resources\CaseStudies\Pages\CreateCaseStudy;
use App\Filament\Resources\CaseStudies\Pages\EditCaseStudy;
use App\Filament\Resources\CaseStudies\Pages\ListCaseStudies;
use App\Filament\Support\ContentResource;
use App\Models\CaseStudy;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CaseStudyResource extends ContentResource
{
    protected static ?string $model = CaseStudy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $modelLabel = 'кейс';

    protected static ?string $pluralModelLabel = 'Кейсы';

    protected static ?int $navigationSort = 1;

    public static function siteUrl(Model $record): string
    {
        return route('cases.show', $record->slug);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('direction')->label('Направление')->required()
                ->options(['training' => 'Обучение', 'events' => 'Мероприятия']),
            ...static::titleWithSlug(),
            TextInput::make('client')->label('Клиент')->required()->maxLength(255),
            TextInput::make('date_label')->label('Дата (текстом)')->required()->maxLength(255),
            TextInput::make('people')->label('Участники')->required()->maxLength(255),
            static::rich('request', 'Запрос'),
            Repeater::make('plan')->label('План')->simple(TextInput::make('step')->required())
                ->addActionLabel('Добавить шаг')->reorderableWithButtons()->required(),
            static::rich('result', 'Результат'),
            static::rich('review', 'Отзыв'),
            Toggle::make('demo')->label('Демо-пример (не реальный клиент)'),
            ImageField::make('cover')->label('Обложка')->aspect(16 / 10)->focal(),
            ImageField::make('photos')->label('Фото')->aspect(4 / 3)->multiple(3),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return static::contentTable($table, [
            TextColumn::make('title')->label('Название')->searchable(),
            TextColumn::make('client')->label('Клиент'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCaseStudies::route('/'),
            'create' => CreateCaseStudy::route('/create'),
            'edit' => EditCaseStudy::route('/{record}/edit'),
        ];
    }
}
