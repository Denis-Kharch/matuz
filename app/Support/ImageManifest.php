<?php

namespace App\Support;

/**
 * Читает public/img/manifest.json, который пишет scripts/images.mjs.
 */
class ImageManifest
{
    private static ?array $data = null;

    /** @return array{widths: list<int>, width: int, height: int} */
    public static function get(string $key): array
    {
        self::$data ??= is_file($file = public_path('img/manifest.json'))
            ? json_decode(file_get_contents($file), true)
            : [];

        return self::$data[$key] ?? ['widths' => [640, 1280], 'width' => 1280, 'height' => 853];
    }
}
