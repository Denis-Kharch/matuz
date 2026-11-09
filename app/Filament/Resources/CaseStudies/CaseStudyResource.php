<?php

namespace App\Filament\Resources\CaseStudies;

use App\Filament\Forms\ImageField;
use App\Filament\Resources\CaseStudies\Pages\EditCaseStudy;
use App\Filament\Resources\CaseStudies\Pages\ListCaseStudies;
use App\Models\CaseStudy;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Кейсы. Пока — название и фото (поле ImageField); остальные поля и создание добавит Task 9. */
class CaseStudyResource extends Resource
{
    protected static ?string $model = CaseStudy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $modelLabel = 'кейс';

    protected static ?string $pluralModelLabel = 'Кейсы';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Название')->required()->maxLength(255),
            ImageField::make('cover')->label('Обложка')->aspect(16 / 10)->focal(),
            ImageField::make('photos')->label('Фото')->aspect(4 / 3)->multiple(3),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')->label('Название')->searchable(),
                TextColumn::make('client')->label('Клиент'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCaseStudies::route('/'),
            'edit' => EditCaseStudy::route('/{record}/edit'),
        ];
    }
}
