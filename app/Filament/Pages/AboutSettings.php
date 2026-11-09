<?php

namespace App\Filament\Pages;

use App\Filament\Forms\ImageField;
use App\Filament\Support\ContentResource;
use App\Models\Setting;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class AboutSettings extends SettingsPage
{
    protected static string|\UnitEnum|null $navigationGroup = 'Страница';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'Об Андрее';

    protected static ?string $title = 'Об Андрее';

    protected static ?string $slug = 'about';

    protected static ?int $navigationSort = 2;

    protected function fields(): array
    {
        return [
            TextInput::make('name')->label('Имя')->required()->maxLength(255),
            Textarea::make('lead')->label('Вводная')->rows(3)->required(),
            Repeater::make('paragraphs')->label('Абзацы')
                ->simple(ContentResource::rich('paragraph', 'Абзац'))
                ->addActionLabel('Добавить абзац')->reorderable()->defaultItems(0),
            TextInput::make('city')->label('Город')->maxLength(255),
            Textarea::make('training_intro')->label('Вступление к разделу «Обучение»')->rows(3),
            ImageField::slot('about')->label('Фото (4:5)')->aspect(4 / 5),
        ];
    }

    protected function load(): array
    {
        $about = Setting::get('about', []);

        return [
            'name' => $about['name'] ?? '',
            'lead' => $about['lead'] ?? '',
            'paragraphs' => array_values($about['paragraphs'] ?? []),
            'city' => $about['city'] ?? '',
            'training_intro' => (string) Setting::get('training_intro', ''),
            'about' => [],
        ];
    }

    protected function persist(array $state): void
    {
        Setting::put('about', [
            'name' => (string) ($state['name'] ?? ''),
            'lead' => (string) ($state['lead'] ?? ''),
            'paragraphs' => static::list($state['paragraphs'] ?? []),
            'city' => (string) ($state['city'] ?? ''),
        ]);
        Setting::put('training_intro', (string) ($state['training_intro'] ?? ''));
    }
}
