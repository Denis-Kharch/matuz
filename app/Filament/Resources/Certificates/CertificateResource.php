<?php

namespace App\Filament\Resources\Certificates;

use App\Filament\Forms\ImageField;
use App\Filament\Resources\Certificates\Pages\CreateCertificate;
use App\Filament\Resources\Certificates\Pages\EditCertificate;
use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Filament\Support\ContentResource;
use App\Models\Certificate;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CertificateResource extends ContentResource
{
    protected static ?string $model = Certificate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $modelLabel = 'регалия';

    protected static ?string $pluralModelLabel = 'Регалии';

    protected static ?int $navigationSort = 4;

    public static function siteUrl(Model $record): string
    {
        return static::anchor('certificates');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Название')->required()->maxLength(255),
            Textarea::make('description')->label('Описание')->required()->rows(3),
            TextInput::make('year')->label('Год')->required()->maxLength(255),
            ImageField::make('image')->label('Фото')->aspect(16 / 10),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return static::contentTable($table, [
            TextColumn::make('title')->label('Название')->searchable(),
            TextColumn::make('year')->label('Год'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificates::route('/'),
            'create' => CreateCertificate::route('/create'),
            'edit' => EditCertificate::route('/{record}/edit'),
        ];
    }
}
