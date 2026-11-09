<?php

namespace App\Filament\Resources\ExperienceStages;

use App\Filament\Forms\ImageField;
use App\Filament\Resources\ExperienceStages\Pages\CreateExperienceStage;
use App\Filament\Resources\ExperienceStages\Pages\EditExperienceStage;
use App\Filament\Resources\ExperienceStages\Pages\ListExperienceStages;
use App\Filament\Support\ContentResource;
use App\Models\ExperienceStage;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ExperienceStageResource extends ContentResource
{
    protected static ?string $model = ExperienceStage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $modelLabel = 'этап опыта';

    protected static ?string $pluralModelLabel = 'Этапы опыта';

    protected static string|\UnitEnum|null $navigationGroup = 'Страница';

    protected static ?int $navigationSort = 2;

    public static function siteUrl(Model $record): string
    {
        return static::anchor('about');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('period')->label('Период')->maxLength(255),
            TextInput::make('title')->label('Заголовок')->required()->maxLength(255),
            Textarea::make('text')->label('Текст')->required()->rows(4),
            ImageField::make('image')->label('Фото')->aspect(16 / 10),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return static::contentTable($table, [
            TextColumn::make('period')->label('Период'),
            TextColumn::make('title')->label('Заголовок')->searchable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExperienceStages::route('/'),
            'create' => CreateExperienceStage::route('/create'),
            'edit' => EditExperienceStage::route('/{record}/edit'),
        ];
    }
}
