<?php

namespace App\Filament\Forms;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

/**
 * Загружаемое фото: JPG, PNG или WebP до 15 МБ, которое GD действительно читает.
 * HEIC и SVG отклоняются понятным сообщением: обработка фото (GD) их не открывает.
 * Строки (уже сохранённые файлы) не проверяются.
 */
class ImageUploadRule implements ValidationRule
{
    public const MAX_KB = 15 * 1024;

    /** Больше — GD не уложится в память при повороте (MediaProcessor: 512 МБ). */
    private const MAX_PIXELS = 40_000_000;

    private const TYPES = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

    public function __construct(private bool $contain = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (Arr::wrap($value) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            if ($message = $this->problem($file)) {
                $fail($message);

                return;
            }
        }
    }

    private function problem(UploadedFile $file): ?string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());

        if (in_array($ext, ['heic', 'heif'], true) || str_contains($mime, 'heic') || str_contains($mime, 'heif')) {
            return 'Формат HEIC (фото с iPhone) не поддерживается. Сохраните фото в JPG: на iPhone — «Настройки → Камера → Форматы → Наиболее совместимый» или «Экспорт» в JPG.';
        }
        if ($ext === 'svg' || str_contains($mime, 'svg')) {
            return $this->contain
                ? 'SVG не поддерживается. Загрузите логотип в PNG с прозрачным фоном (или WebP).'
                : 'SVG не подходит для фото. Загрузите JPG, PNG или WebP.';
        }
        if ($file->getSize() > self::MAX_KB * 1024) {
            return 'Файл больше 15 МБ. Уменьшите фото и загрузите снова.';
        }
        $info = @getimagesize($file->getRealPath());
        if (! $info || ! isset(self::TYPES[$info[2]]) || $info[0] < 1 || $info[1] < 1) {
            return 'Файл не открывается как фото. Подходят JPG, PNG и WebP.';
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            return 'Фото слишком большое: больше 40 мегапикселей. Уменьшите его и загрузите снова.';
        }

        return null;
    }
}
