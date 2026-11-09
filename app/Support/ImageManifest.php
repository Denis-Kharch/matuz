<?php

namespace App\Support;

use App\Models\Media;

/**
 * Метаданные картинки для <x-picture>.
 * Ключи `media/…` — фото из таблицы media (варианты лежат в {key}-{w}.{fmt}),
 * остальные — статичные картинки из public/img/manifest.json (пишет scripts/images.mjs).
 */
class ImageManifest
{
    private static ?array $static = null;

    /** @var array<string, array|null>|null кэш записей media на запрос */
    private static ?array $media = null;

    /** @return array{base:string, widths:list<int>, width:int, height:int, formats:list<string>, focal:?array{x:int,y:int}} */
    public static function get(string $key): array
    {
        return str_starts_with($key, 'media/') ? self::media($key) : self::static($key);
    }

    /** Сбросить кэш (после изменения фото). */
    public static function flush(): void
    {
        self::$static = null;
        self::$media = null;
    }

    private static function static(string $key): array
    {
        self::$static ??= is_file($file = public_path('img/manifest.json'))
            ? json_decode(file_get_contents($file), true)
            : [];
        $entry = self::$static[$key] ?? ['widths' => [640, 1280], 'width' => 1280, 'height' => 853];

        return [
            'base' => "img/{$key}",
            'widths' => $entry['widths'],
            'width' => $entry['width'],
            'height' => $entry['height'],
            'formats' => ['avif', 'webp'],
            'focal' => null,
        ];
    }

    private static function media(string $key): array
    {
        // Все записи одним запросом; незнакомый ключ — один дозапрос и запоминаем результат.
        self::$media ??= Media::query()->whereNotNull('key')->get()->filter(fn (Media $m) => $m->widths)
            ->mapWithKeys(fn (Media $m) => [$m->key => self::fromMedia($m)])->all();
        if (! array_key_exists($key, self::$media)) {
            $m = Media::query()->where('key', $key)->first();
            self::$media[$key] = $m?->widths ? self::fromMedia($m) : null;
        }

        // Нет записи (или ещё нет вариантов) — пустые widths: <x-picture> ничего не выводит,
        // чтобы не ссылаться на несуществующие файлы.
        return self::$media[$key] ?? [
            'base' => $key,
            'widths' => [],
            'width' => 1280,
            'height' => 853,
            'formats' => ['webp'],
            'focal' => null,
        ];
    }

    private static function fromMedia(Media $m): array
    {
        return [
            'base' => $m->key,
            'widths' => $m->widths ?? [],
            'width' => $m->width,
            'height' => $m->height,
            'formats' => $m->formats ?? ['webp'],
            'focal' => ['x' => $m->focal_x, 'y' => $m->focal_y],
        ];
    }
}
