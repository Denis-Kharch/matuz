<?php

namespace App\Content;

/**
 * Блоки главной под первым экраном: названия, порядок по умолчанию и приведение сохранённого порядка
 * (неизвестные ключи отбрасываются, новые блоки добавляются в конец видимыми). Первый экран всегда первый.
 */
class HomeSections
{
    /** Ключ → название в админке (ключ = шаблон sections/{ключ}.blade.php и якорь #{ключ}). */
    public const LABELS = [
        'marquee' => 'Бегущая строка принципов',
        'about' => 'Об Андрее',
        'directions' => 'Направления работы',
        'cases' => 'Кейсы',
        'events' => 'Лента событий',
        'partners' => 'Друзья и партнёры',
        'faq' => 'Частые вопросы',
        'gallery' => 'Галерея',
        'certificates' => 'Регалии и сертификаты',
        'contacts' => 'Контакты',
    ];

    /**
     * @param  mixed  $saved  list<array{key:string, visible:bool}> из настроек (или что угодно)
     * @return list<array{key:string, visible:bool}>
     */
    public static function normalize(mixed $saved): array
    {
        $out = [];
        foreach (is_array($saved) ? $saved : [] as $row) {
            $key = is_array($row) ? ($row['key'] ?? null) : null;
            if (is_string($key) && isset(self::LABELS[$key]) && ! isset($out[$key])) {
                $out[$key] = ['key' => $key, 'visible' => (bool) ($row['visible'] ?? true)];
            }
        }
        foreach (array_keys(self::LABELS) as $key) {
            $out[$key] ??= ['key' => $key, 'visible' => true];
        }

        return array_values($out);
    }

    /** @return list<array{key:string, visible:bool}> */
    public static function defaults(): array
    {
        return self::normalize([]);
    }
}
