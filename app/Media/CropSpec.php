<?php

namespace App\Media;

/**
 * Кадрирование фото в соглашении Cropper.js (getData / getCroppedCanvas):
 * после ориентации EXIF — отражения flipX/flipY (scaleX/scaleY = -1) в системе
 * самого кадра, затем поворот rotate градусов по часовой; прямоугольник
 * x/y/width/height — в пикселях повёрнутого холста. Null-прямоугольник = весь кадр.
 */
final readonly class CropSpec
{
    public function __construct(
        public ?int $x = null,
        public ?int $y = null,
        public ?int $width = null,
        public ?int $height = null,
        public float $rotate = 0,
        public bool $flipX = false,
        public bool $flipY = false,
    ) {}

    public static function fromArray(?array $crop): self
    {
        if (! $crop) {
            return new self;
        }
        $int = fn (string $k) => isset($crop[$k]) ? (int) round((float) $crop[$k]) : null;

        return new self(
            x: $int('x'),
            y: $int('y'),
            width: $int('width'),
            height: $int('height'),
            rotate: fmod(fmod((float) ($crop['rotate'] ?? 0), 360) + 360, 360),
            flipX: (bool) ($crop['flipX'] ?? false),
            flipY: (bool) ($crop['flipY'] ?? false),
        );
    }

    public function hasRect(): bool
    {
        return $this->width > 0 && $this->height > 0;
    }

    /**
     * Прямоугольник, ограниченный размерами изображения.
     *
     * @return array{x:int, y:int, width:int, height:int}
     */
    public function clampTo(int $imageWidth, int $imageHeight): array
    {
        if (! $this->hasRect()) {
            return ['x' => 0, 'y' => 0, 'width' => $imageWidth, 'height' => $imageHeight];
        }
        $x = max(0, min($this->x ?? 0, $imageWidth - 1));
        $y = max(0, min($this->y ?? 0, $imageHeight - 1));

        return [
            'x' => $x,
            'y' => $y,
            'width' => max(1, min($this->width, $imageWidth - $x)),
            'height' => max(1, min($this->height, $imageHeight - $y)),
        ];
    }
}
