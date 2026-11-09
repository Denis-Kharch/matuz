<?php

namespace App\Filament\Pages;

use App\Filament\Forms\ImageField;
use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class HeroSettings extends SettingsPage
{
    protected static string|\UnitEnum|null $navigationGroup = 'Страница';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Первый экран';

    protected static ?string $title = 'Первый экран';

    protected static ?string $slug = 'hero';

    protected static ?int $navigationSort = 1;

    protected function fields(): array
    {
        return [
            TextInput::make('title')->label('Заголовок')->required()->maxLength(255),
            TextInput::make('subtitle')->label('Подзаголовок')->maxLength(255),
            Textarea::make('note')->label('Примечание под кнопкой')->rows(2)->maxLength(500),
            Select::make('subject_side')->label('Где на фото человек')
                ->options(['left' => 'Слева', 'right' => 'Справа'])->required()->native(false)
                ->helperText('Текст на сайте ложится на противоположную сторону.'),
            ImageField::slot('hero')->label('Фото (16:9)')->aspect(16 / 9)->focal(),
            ImageField::slot('hero_mobile')->label('Фото для телефона (9:16)')->aspect(9 / 16),
        ];
    }

    protected function load(): array
    {
        return ['subject_side' => 'left', ...Setting::get('hero', []), 'hero' => [], 'hero_mobile' => []];
    }

    protected function persist(array $state): void
    {
        Setting::put('hero', [
            'title' => (string) ($state['title'] ?? ''),
            'subtitle' => (string) ($state['subtitle'] ?? ''),
            'note' => (string) ($state['note'] ?? ''),
            'subject_side' => ($state['subject_side'] ?? 'left') === 'right' ? 'right' : 'left',
        ]);
    }
}
