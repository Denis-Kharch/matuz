<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class KpSettings extends SettingsPage
{
    protected static string|\UnitEnum|null $navigationGroup = 'КП';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Коммерческое предложение';

    protected static ?string $title = 'Коммерческое предложение';

    protected static ?string $slug = 'kp';

    protected static ?int $navigationSort = 1;

    protected function fields(): array
    {
        return [
            TextInput::make('subtitle')->label('Подзаголовок')->maxLength(255),
            Textarea::make('why')->label('Почему вы дочитаете')->rows(4),
            Textarea::make('who')->label('Кто проводит')->rows(4),
            Textarea::make('focus')->label('Акцент')->rows(4),
            TagsInput::make('formats')->label('Форматы')->helperText('Введите формат и нажмите Enter.'),
            TextInput::make('formats_note')->label('Примечание к форматам')->maxLength(500),
            Repeater::make('prices')->label('Цены')
                ->schema([
                    TextInput::make('label')->label('Услуга')->required(),
                    TextInput::make('price')->label('Цена')->required()->maxLength(100),
                ])->columns(2)->addActionLabel('Добавить цену')->reorderable()->defaultItems(0),
            Repeater::make('price_factors')->label('От чего зависит цена')
                ->schema([
                    TextInput::make('title')->label('Название')->required(),
                    Textarea::make('text')->label('Текст')->rows(2)->required(),
                ])->addActionLabel('Добавить фактор')->reorderable()->defaultItems(0),
            Textarea::make('terms')->label('Условия (после «ФЗ № 422»)')->rows(3),
            Repeater::make('topics')->label('Темы')
                ->schema([
                    TextInput::make('title')->label('Название группы')->required(),
                    Repeater::make('items')->label('Пункты')->schema([TextInput::make('item')->hiddenLabel()->required()])
                        ->addActionLabel('Добавить пункт')->reorderable()->defaultItems(0),
                ])->addActionLabel('Добавить группу тем')->reorderable()->defaultItems(0),
            Repeater::make('achievements')->label('Достижения')
                ->schema([TextInput::make('achievement')->hiddenLabel()->required()])
                ->addActionLabel('Добавить достижение')->reorderable()->defaultItems(0),
            TextInput::make('final')->label('Финальная фраза')->maxLength(500),
        ];
    }

    protected function load(): array
    {
        $kp = Setting::get('kp', []);
        foreach ($kp['topics'] ?? [] as $i => $topic) {
            $kp['topics'][$i]['items'] = static::wrap($topic['items'] ?? [], 'item');
        }
        $kp['achievements'] = static::wrap($kp['achievements'] ?? [], 'achievement');

        return $kp;
    }

    protected function persist(array $state): void
    {
        Setting::put('kp', [
            'subtitle' => (string) ($state['subtitle'] ?? ''),
            'why' => (string) ($state['why'] ?? ''),
            'who' => (string) ($state['who'] ?? ''),
            'focus' => (string) ($state['focus'] ?? ''),
            'formats' => static::list($state['formats'] ?? []),
            'formats_note' => (string) ($state['formats_note'] ?? ''),
            'prices' => array_map(fn ($p) => ['label' => (string) $p['label'], 'price' => (string) $p['price']], static::list($state['prices'] ?? [])),
            'price_factors' => array_map(fn ($f) => ['title' => (string) $f['title'], 'text' => (string) $f['text']], static::list($state['price_factors'] ?? [])),
            'terms' => (string) ($state['terms'] ?? ''),
            'topics' => array_map(fn ($t) => ['title' => (string) $t['title'], 'items' => static::unwrap($t['items'] ?? [], 'item')], static::list($state['topics'] ?? [])),
            'achievements' => static::unwrap($state['achievements'] ?? [], 'achievement'),
            'final' => (string) ($state['final'] ?? ''),
        ]);
    }
}
