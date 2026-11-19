<?php

namespace App\Filament\Pages;

use App\Content\HomeSections;
use App\Models\Setting;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

/** Порядок и видимость блоков главной (первый экран всегда первый). Меню сайта следует этому порядку. */
class HomeSectionsSettings extends SettingsPage
{
    protected static string|\UnitEnum|null $navigationGroup = 'Страница';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Блоки главной';

    protected static ?string $title = 'Блоки главной страницы';

    protected static ?string $slug = 'home-sections';

    protected static ?int $navigationSort = 0;

    protected function fields(): array
    {
        return [
            Repeater::make('sections')->hiddenLabel()
                ->helperText('Перетащите блок за ручку или двигайте стрелками. Первый экран всегда наверху. '
                    .'Выключенный блок не показывается на сайте и пропадает из меню.')
                ->schema([
                    Hidden::make('key'),
                    Toggle::make('visible')->label('Показывать на сайте')->inline(),
                ])
                ->itemLabel(fn (array $state): string => HomeSections::LABELS[$state['key'] ?? ''] ?? '—')
                ->addable(false)->deletable(false)
                ->reorderable()->reorderableWithButtons()
                ->collapsible(false),
        ];
    }

    protected function load(): array
    {
        return ['sections' => HomeSections::normalize(Setting::get('home_sections', []))];
    }

    protected function persist(array $state): void
    {
        Setting::put('home_sections', HomeSections::normalize(array_values($state['sections'] ?? [])));
    }
}
