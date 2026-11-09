<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'collection' => 'default',
            'original_path' => fn () => self::fakeJpeg(1280, 853),
        ];
    }

    public function cover(): static
    {
        return $this->state(fn () => [
            'collection' => 'cover',
            'original_path' => fn () => self::fakeJpeg(3000, 2000),
            'crop' => ['x' => 0, 'y' => 0, 'width' => 1600, 'height' => 1000, 'rotate' => 0, 'flipX' => false, 'flipY' => false],
        ]);
    }

    /** Сгенерированный GD jpg на диске local; возвращает путь. */
    public static function fakeJpeg(int $width, int $height, ?string $name = null): string
    {
        ini_set('memory_limit', '512M');
        $im = imagecreatetruecolor($width, $height);
        imagefill($im, 0, 0, imagecolorallocate($im, 40, 90, 160));
        imagefilledrectangle($im, 0, 0, intdiv($width, 4), intdiv($height, 4), imagecolorallocate($im, 220, 60, 40));
        ob_start();
        imagejpeg($im, null, 80);
        $path = 'media-originals/'.($name ?? bin2hex(random_bytes(6)).'.jpg');
        Storage::disk('local')->put($path, ob_get_clean());

        return $path;
    }
}
