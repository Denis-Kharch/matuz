<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Notifications\TelegramNotifier;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class SiteSettings extends SettingsPage
{
    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Сайт';

    protected static ?string $title = 'Настройки сайта';

    protected static ?string $slug = 'site';

    protected static ?int $navigationSort = 1;

    protected function fields(): array
    {
        return [
            Section::make('Поисковики')->schema([
                Toggle::make('noindex')->label('Закрыть сайт от поисковиков')
                    ->helperText('Пока сайт наполняется, оставьте включённым. Перед запуском выключите.'),
            ]),
            Section::make('Уведомления в Telegram')->schema([
                TextInput::make('token')->label('Токен бота')->password()->revealable()
                    ->autocomplete('new-password')
                    ->placeholder(fn () => $this->hasToken() ? 'Сохранён — оставьте пустым, чтобы не менять' : null)
                    ->helperText('Выдаёт BotFather. После сохранения не показывается.'),
                TextInput::make('chat_id')->label('Chat ID')->maxLength(100),
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('telegramTest')->label('Отправить тестовое сообщение в Telegram')
                ->color('gray')->action(fn () => $this->sendTest()),
        ];
    }

    protected function load(): array
    {
        return [
            'noindex' => (bool) (Setting::get('site', [])['noindex'] ?? true),
            'token' => null,
            'chat_id' => Setting::get('telegram', [])['chat_id'] ?? '',
        ];
    }

    protected function persist(array $state): void
    {
        Setting::put('site', ['noindex' => (bool) ($state['noindex'] ?? false)]);

        $telegram = Setting::get('telegram', []);
        Setting::put('telegram', [
            // Пустое поле — оставить прежний токен.
            'token' => filled($state['token'] ?? null) ? $state['token'] : ($telegram['token'] ?? ''),
            'chat_id' => trim((string) ($state['chat_id'] ?? '')),
        ]);
    }

    private function hasToken(): bool
    {
        return filled(Setting::get('telegram', [])['token'] ?? null);
    }

    public function sendTest(): void
    {
        $ok = TelegramNotifier::send(
            'Тестовое сообщение с сайта Андрея Матузова: уведомления работают.',
            $this->data['token'] ?? null,
            $this->data['chat_id'] ?? null,
        );

        $ok
            ? Notification::make()->title('Сообщение отправлено')->success()->send()
            : Notification::make()->title('Не удалось отправить')->body('Проверьте токен бота и chat ID. Бот должен быть добавлен в чат.')->danger()->send();
    }
}
