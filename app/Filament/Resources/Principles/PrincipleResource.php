<?php

namespace App\Filament\Resources\Principles;

use App\Filament\Resources\Principles\Pages\CreatePrinciple;
use App\Filament\Resources\Principles\Pages\EditPrinciple;
use App\Filament\Resources\Principles\Pages\ListPrinciples;
use App\Filament\Support\ContentResource;
use App\Models\Principle;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PrincipleResource extends ContentResource
{
    protected static ?string $model = Principle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?string $modelLabel = 'принцип';

    protected static ?string $pluralModelLabel = 'Принципы';

    protected static string|\UnitEnum|null $navigationGroup = 'Страница';

    protected static ?int $navigationSort = 1;

    public static function siteUrl(Model $record): string
    {
        return static::anchor('about');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('text')->label('Текст')->required()->rows(3),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return static::contentTable($table, [
            TextColumn::make('text')->label('Текст')->limit(80)->wrap(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrinciples::route('/'),
            'create' => CreatePrinciple::route('/create'),
            'edit' => EditPrinciple::route('/{record}/edit'),
        ];
    }
}
