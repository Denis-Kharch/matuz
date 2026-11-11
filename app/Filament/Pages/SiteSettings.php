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
use Illuminate\Support\Facades\Crypt;

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
            Section::make('Поисковики и аналитика')->schema([
                TextInput::make('yandex_verification')->label('Код подтверждения Яндекс.Вебмастера')->maxLength(100)
                    ->helperText('Вебмастер → Добавить сайт → «Мета-тег»: из <meta name="yandex-verification" content="…"> скопируйте только то, что в content.'),
                TextInput::make('google_verification')->label('Код подтверждения Google Search Console')->maxLength(100)
                    ->helperText('Search Console → «Тег HTML»: только значение content из <meta name="google-site-verification" …>.'),
                TextInput::make('metrika_id')->label('Номер счётчика Яндекс.Метрики')->maxLength(20)
                    ->rule('nullable')->regex('/^\d*$/')->validationMessages(['regex' => 'Только цифры — номер счётчика.'])
                    ->helperText('Только цифры. Счётчик включается у посетителя после «Принять» в окне cookie; формирование КП — цель «kp_lead».'),
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
            'yandex_verification' => Setting::get('seo', [])['yandex_verification'] ?? '',
            'google_verification' => Setting::get('seo', [])['google_verification'] ?? '',
            'metrika_id' => Setting::get('seo', [])['metrika_id'] ?? '',
            'token' => null,
            'chat_id' => Setting::get('telegram', [])['chat_id'] ?? '',
        ];
    }

    protected function persist(array $state): void
    {
        Setting::put('site', ['noindex' => (bool) ($state['noindex'] ?? false)]);
        Setting::put('seo', [
            'yandex_verification' => self::verificationCode($state['yandex_verification'] ?? null),
            'google_verification' => self::verificationCode($state['google_verification'] ?? null),
            'metrika_id' => trim((string) ($state['metrika_id'] ?? '')),
        ]);

        $telegram = Setting::get('telegram', []);
        Setting::put('telegram', [
            // Пустое поле — оставить прежний токен.
            'token' => filled($state['token'] ?? null) ? Crypt::encryptString(trim($state['token'])) : ($telegram['token'] ?? ''),
            'chat_id' => trim((string) ($state['chat_id'] ?? '')),
        ]);
    }

    /** Код подтверждения: если вставили тег целиком — берём значение content; лишнее отбрасываем. */
    public static function verificationCode(?string $value): string
    {
        $value = trim((string) $value);
        if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $value, $m)) {
            $value = $m[1];
        }

        return preg_replace('/[^A-Za-z0-9_\-:]/', '', $value);
    }

    private function hasToken(): bool
    {
        return filled(Setting::get('telegram', [])['token'] ?? null);
    }

    public function sendTest(): void
    {
        $ok = TelegramNotifier::send(
            'Тестовое сообщение с сайта Андрея Матузова: уведомления работают.',
            filled($this->data['token'] ?? null) ? trim($this->data['token']) : null,
            filled($this->data['chat_id'] ?? null) ? trim($this->data['chat_id']) : null,
        );

        $ok
            ? Notification::make()->title('Сообщение отправлено')->success()->send()
            : Notification::make()->title('Не удалось отправить')->body('Проверьте токен бота и chat ID. Бот должен быть добавлен в чат.')->danger()->send();
    }
}
