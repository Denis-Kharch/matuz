<?php

namespace App\Support;

use Intervention\Image\ImageManager;
use Throwable;

/**
 * Картинка превью для соцсетей и мессенджеров (og:image): JPG 1200×630 из самого крупного варианта фото,
 * кадр по точке фокуса. JPG — потому что WebP понимают не все (VK, часть мессенджеров).
 * Файл og/{хеш}.jpg в публичной папке создаётся один раз; меняется фото — меняется и хеш.
 */
class OgImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /** Адрес JPG-превью; если сделать не удалось — адрес самого крупного WebP (лучше, чем ничего). */
    public static function url(?string $key): ?string
    {
        if (! $key) {
            return null;
        }
        $meta = ImageManifest::get($key);
        if (! $meta['widths']) {
            return null;
        }
        $w = last($meta['widths']);
        $source = public_path("{$meta['base']}-{$w}.webp");
        $focal = $meta['focal'] ?? ['x' => 50, 'y' => 50];
        $name = 'og/'.substr(sha1($meta['base'].'|'.$w.'|'.$focal['x'].'|'.$focal['y'].'|'.(is_file($source) ? filesize($source) : 0)), 0, 16).'.jpg';
        $target = public_path($name);

        if (! is_file($target)) {
            try {
                self::render($source, $target, $focal);
            } catch (Throwable $e) {
                report($e);

                return asset("{$meta['base']}-{$w}.webp");
            }
        }

        return asset($name);
    }

    /** @param array{x:int,y:int} $focal */
    private static function render(string $source, string $target, array $focal): void
    {
        $image = ImageManager::gd()->read($source);
        // Кадр 1200×630 по точке фокуса (как object-position на сайте).
        $scale = max(self::WIDTH / $image->width(), self::HEIGHT / $image->height());
        $image->scale((int) ceil($image->width() * $scale), (int) ceil($image->height() * $scale));
        $x = (int) round(max(0, min($image->width() - self::WIDTH, $image->width() * $focal['x'] / 100 - self::WIDTH / 2)));
        $y = (int) round(max(0, min($image->height() - self::HEIGHT, $image->height() * $focal['y'] / 100 - self::HEIGHT / 2)));
        $image->crop(self::WIDTH, self::HEIGHT, $x, $y);

        is_dir(dirname($target)) || mkdir(dirname($target), 0755, true);
        $tmp = $target.'.'.bin2hex(random_bytes(4)).'.tmp';
        $image->toJpeg(82)->save($tmp);
        rename($tmp, $target);
    }
}
