<?php

namespace Tests\Support;

use App\Filament\Forms\ImageField;
use Closure;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * Форма-заглушка для тестов ImageField: поля задаёт тест через $components,
 * сохранение — как у страницы ресурса (getState сохраняет фото владельца) + слоты настроек.
 */
class TestImageForm extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions, InteractsWithSchemas;

    /** @var (Closure(): array)|null */
    public static ?Closure $components = null;

    public ?Model $record = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->record?->attributesToArray() ?? []);
    }

    public function form(Schema $schema): Schema
    {
        $components = static::$components
            ? (static::$components)()
            : [ImageField::make('photos')->aspect(4 / 3)->multiple()];

        return $schema->components($components)->model($this->record)->statePath('data');
    }

    public function save(): void
    {
        $this->form->getState();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
