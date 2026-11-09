<?php

namespace App\Filament\Resources\TopicGroups;

use App\Filament\Resources\TopicGroups\Pages\CreateTopicGroup;
use App\Filament\Resources\TopicGroups\Pages\EditTopicGroup;
use App\Filament\Resources\TopicGroups\Pages\ListTopicGroups;
use App\Filament\Support\ContentResource;
use App\Models\TopicGroup;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TopicGroupResource extends ContentResource
{
    protected static ?string $model = TopicGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $modelLabel = 'группа тем';

    protected static ?string $pluralModelLabel = 'Темы обучения';

    protected static ?int $navigationSort = 8;

    public static function siteUrl(Model $record): string
    {
        return static::anchor('directions');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Название группы')->required()->maxLength(255),
            Repeater::make('topics')->label('Карточки')
                ->relationship('topics')
                ->orderColumn('sort')
                ->schema([
                    TextInput::make('title')->label('Название')->required()->maxLength(255),
                    Textarea::make('text')->label('Текст')->required()->rows(3),
                ])
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->addActionLabel('Добавить карточку')
                ->collapsible(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return static::contentTable($table, [
            TextColumn::make('title')->label('Группа')->searchable(),
            TextColumn::make('topics_count')->label('Карточек')->counts('topics'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTopicGroups::route('/'),
            'create' => CreateTopicGroup::route('/create'),
            'edit' => EditTopicGroup::route('/{record}/edit'),
        ];
    }
}
