<?php

namespace App\Media;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use RuntimeException;
use Throwable;

/**
 * Нарезает оригинал фото в webp(+avif) варианты в папке сайта (public_path()):
 * {key}-{w}.webp / .avif — как scripts/images.mjs для статичных картинок.
 */
class MediaProcessor
{
    public const WIDTHS = [640, 1280, 1920];

    private const QUALITY = ['avif' => 50, 'webp' => 72];

    /**
     * @throws MediaProcessingFailed если оригинал не читается/не кодируется;
     *                               прежние файлы и key при этом не трогаются.
     */
    public function process(Media $m): void
    {
        ini_set('memory_limit', '512M');

        $key = 'media/'.$m->getKey().'-'.bin2hex(random_bytes(4));
        try {
            [$widths, $formats, $width, $height] = $this->render($m, $key);
        } catch (Throwable $e) {
            $this->deleteKeyFiles($key);
            throw new MediaProcessingFailed($e);
        }

        $oldKey = $m->key;
        $m->forceFill([
            'key' => $key,
            'widths' => $widths,
            'formats' => $formats,
            'width' => $width,
            'height' => $height,
        ])->saveQuietly();
        $this->deleteKeyFiles($oldKey);
    }

    /** Удаляет файлы текущего key записи. */
    public function deleteVariants(Media $m): void
    {
        $this->deleteKeyFiles($m->key);
    }

    /** @return array{0:list<int>, 1:list<string>, 2:int, 3:int} */
    private function render(Media $m, string $key): array
    {
        $bytes = Storage::disk('local')->get($m->original_path)
            ?? throw new RuntimeException("Оригинал не найден: {$m->original_path}");
        // Ориентацию читаем сами: ext-exif есть не везде, а автоповорот Intervention зависит от него.
        $image = ImageManager::gd(autoOrientation: false)->read($bytes);
        $image = $this->orient($image, self::exifOrientation($bytes));

        // Как Cropper.js getCroppedCanvas: отражение в собственной системе кадра,
        // затем поворот по часовой; прямоугольник кадра — в координатах повёрнутого холста.
        $spec = CropSpec::fromArray($m->crop);
        if ($spec->flipX) {
            $image->flop();
        }
        if ($spec->flipY) {
            $image->flip();
        }
        if ($spec->rotate != 0) {
            $image->rotate(-$spec->rotate); // Intervention крутит против часовой
        }
        if ($spec->hasRect()) {
            $r = $spec->clampTo($image->width(), $image->height());
            $image->crop($r['width'], $r['height'], $r['x'], $r['y']);
        }

        $width = $image->width();
        $height = $image->height();
        $widths = array_values(array_filter(self::WIDTHS, fn ($w) => $w <= $width)) ?: [$width];
        $formats = function_exists('imageavif') ? ['avif', 'webp'] : ['webp'];

        $dir = dirname(public_path($key));
        is_dir($dir) || mkdir($dir, 0755, true);

        // От большей ширины к меньшей: каждое уменьшение — из предыдущего шага.
        foreach (array_reverse($widths) as $w) {
            $image->scaleDown(width: $w);
            foreach ($formats as $format) {
                $encoded = $format === 'avif'
                    ? $image->toAvif(quality: self::QUALITY['avif'])
                    : $image->toWebp(quality: self::QUALITY['webp']);
                $encoded->save(public_path("{$key}-{$w}.{$format}"));
            }
        }

        return [$widths, $formats, $width, $height];
    }

    private function deleteKeyFiles(?string $key): void
    {
        if (! $key) {
            return;
        }
        foreach (glob(public_path($key).'-*') ?: [] as $file) {
            if (preg_match('/-\d+\.(webp|avif)$/', $file)) {
                @unlink($file);
            }
        }
    }

    private function orient(ImageInterface $image, int $orientation): ImageInterface
    {
        return match ($orientation) {
            2 => $image->flop(),
            3 => $image->rotate(180),
            4 => $image->rotate(180)->flop(),
            5 => $image->rotate(270)->flop(),
            6 => $image->rotate(270),
            7 => $image->rotate(90)->flop(),
            8 => $image->rotate(90),
            default => $image,
        };
    }

    /** Orientation из EXIF (APP1) JPEG-файла; 1, если её нет. */
    public static function exifOrientation(string $bytes): int
    {
        if (! str_starts_with($bytes, "\xFF\xD8")) {
            return 1;
        }
        $pos = 2;
        $len = strlen($bytes);
        while ($pos + 4 <= $len && $bytes[$pos] === "\xFF") {
            $marker = ord($bytes[$pos + 1]);
            if ($marker === 0xDA || $marker === 0xD9) {
                break; // начались данные изображения
            }
            $size = unpack('n', substr($bytes, $pos + 2, 2))[1];
            if ($marker === 0xE1 && substr($bytes, $pos + 4, 6) === "Exif\0\0") {
                return self::tiffOrientation(substr($bytes, $pos + 10, $size - 8));
            }
            $pos += 2 + $size;
        }

        return 1;
    }

    private static function tiffOrientation(string $tiff): int
    {
        $le = str_starts_with($tiff, 'II');
        if (! $le && ! str_starts_with($tiff, 'MM')) {
            return 1;
        }
        $u16 = fn (int $at) => strlen($tiff) >= $at + 2 ? unpack($le ? 'v' : 'n', substr($tiff, $at, 2))[1] : 0;
        $u32 = fn (int $at) => strlen($tiff) >= $at + 4 ? unpack($le ? 'V' : 'N', substr($tiff, $at, 4))[1] : 0;

        $ifd = $u32(4);
        $count = $u16($ifd);
        for ($i = 0; $i < $count; $i++) {
            $entry = $ifd + 2 + $i * 12;
            if ($u16($entry) === 0x0112) {
                $value = $u16($entry + 8);

                return $value >= 1 && $value <= 8 ? $value : 1;
            }
        }

        return 1;
    }
}
