<?php

namespace App\Filament\Resources\Partners;

use App\Filament\Forms\ImageField;
use App\Filament\Resources\Partners\Pages\CreatePartner;
use App\Filament\Resources\Partners\Pages\EditPartner;
use App\Filament\Resources\Partners\Pages\ListPartners;
use App\Filament\Support\ContentResource;
use App\Models\Partner;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PartnerResource extends ContentResource
{
    protected static ?string $model = Partner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $modelLabel = 'партнёр';

    protected static ?string $pluralModelLabel = 'Партнёры';

    protected static ?int $navigationSort = 3;

    public static function siteUrl(Model $record): string
    {
        return static::anchor('partners');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Название')->required()->maxLength(255),
            TextInput::make('short')->label('Короткое имя')->required()->maxLength(255),
            Textarea::make('description')->label('Описание')->required()->rows(3),
            TextInput::make('url')->label('Ссылка')->url()->maxLength(255),
            ImageField::make('logo')->label('Логотип')->contain(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return static::contentTable($table, [
            TextColumn::make('name')->label('Название')->searchable(),
            TextColumn::make('url')->label('Ссылка'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartners::route('/'),
            'create' => CreatePartner::route('/create'),
            'edit' => EditPartner::route('/{record}/edit'),
        ];
    }
}
