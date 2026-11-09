<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Отправка сообщения в Telegram-чат через бота. Настройки — Setting «telegram» (token, chat_id). */
class TelegramNotifier
{
    /** Токен и chat id можно передать явно (проверка из формы до сохранения). Никогда не бросает исключений. */
    public static function send(string $text, ?string $token = null, ?string $chatId = null): bool
    {
        try {
            $s = Setting::get('telegram', []);
            $token = $token ?: ($s['token'] ?? '');
            $chatId = $chatId ?: ($s['chat_id'] ?? '');
            if ($token === '' || $chatId === '') {
                return false;
            }

            return Http::timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => $text])
                ->successful();
        } catch (Throwable) {
            return false;
        }
    }
}
