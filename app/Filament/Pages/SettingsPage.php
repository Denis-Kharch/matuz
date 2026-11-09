<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;

/**
 * Основа страниц настроек: одна форма, значения лежат в таблице settings (Setting::put → публикация),
 * одиночные фото — слоты ImageField::slot().
 */
abstract class SettingsPage extends Page
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @return array<Component> */
    abstract protected function fields(): array;

    /** Значения формы при открытии. */
    abstract protected function load(): array;

    /** Сохранение значений формы (getState уже сохранил фото слотов). */
    abstract protected function persist(array $state): void;

    public function mount(): void
    {
        $this->form->fill($this->load());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->fields())->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Сохранить')->submit('save')->keyBindings(['mod+s']),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $this->persist($this->form->getState());

        Notification::make()->title('Сохранено')->body('Сайт обновится в течение минуты.')->success()->send();
    }

    /** Список из состояния Repeater (ключи-uuid) без пустых значений. */
    protected static function list(?array $items): array
    {
        return array_values(array_filter($items ?? [], fn ($v) => $v !== null && $v !== '' && $v !== []));
    }
}
