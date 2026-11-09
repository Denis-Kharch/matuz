<?php

namespace App\Filament\Support;

use App\Site\PublishQueue;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Общая основа ресурсов контента сайта: таблица с перетаскиванием и переключателем «На сайте»,
 * действия «Посмотреть на сайте» / изменить / удалить, rich-поле и slug из названия.
 */
abstract class ContentResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    /** Адрес записи (или её раздела) на публичном сайте. */
    abstract public static function siteUrl(Model $record): string;

    /**
     * @param  array<Column>  $columns
     */
    protected static function contentTable(Table $table, array $columns): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            // Перетаскивание обновляет строки одним запросом мимо событий модели — публикацию просим сами.
            ->afterReordering(fn () => app(PublishQueue::class)->request())
            ->paginated(false)
            ->columns([
                ...$columns,
                ToggleColumn::make('is_visible')->label('На сайте'),
            ])
            ->recordActions([
                Action::make('view-on-site')
                    ->label('Посмотреть на сайте')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Model $record): string => static::siteUrl($record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /** Rich-поле: жирный, курсив, ссылка, списки (вывод на сайте чистит App\Support\RichText). */
    public static function rich(string $name, string $label): RichEditor
    {
        return RichEditor::make($name)
            ->label($label)
            ->required()
            ->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList']]);
    }

    /** Название, из которого при создании строится slug; slug можно править, он уникален. */
    protected static function titleWithSlug(): array
    {
        return [
            TextInput::make('title')->label('Название')->required()->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, ?string $old, Get $get, Set $set, string $operation): void {
                    $current = (string) $get('slug');
                    // Не трогаем slug, который уже правили руками, и slug опубликованной записи.
                    if ($operation === 'create' && ($current === '' || $current === static::slugify((string) $old))) {
                        $set('slug', static::slugify((string) $state));
                    }
                }),
            TextInput::make('slug')->label('Адрес (slug)')->required()->maxLength(255)
                ->unique(ignoreRecord: true)
                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->validationMessages(['regex' => 'Только латиница, цифры и дефисы.'])
                ->helperText('Часть ссылки на сайте. Строится из названия, можно изменить.'),
        ];
    }

    public static function slugify(string $title): string
    {
        return Str::slug($title, '-', 'ru');
    }

    /** Якорь раздела на главной: url('/').'#faq'. */
    protected static function anchor(string $anchor): string
    {
        return url('/').'#'.$anchor;
    }
}
