<?php

use App\Media\MediaProcessingFailed;
use App\Models\Concerns\HasMedia;
use App\Models\Media;
use App\Support\ImageManifest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class, StoresMediaFiles::class);

class MediaOwnerStub extends Model
{
    use HasMedia;

    protected $table = 'topic_groups';

    protected $guarded = [];
}

beforeEach(function () {
    Storage::fake('local');
    $this->publicDir = sys_get_temp_dir().'/media-test-'.bin2hex(random_bytes(4));
    mkdir($this->publicDir);
    $this->originalPublic = public_path();
    app()->usePublicPath($this->publicDir);
});

afterEach(function () {
    app()->usePublicPath($this->originalPublic);
    File::deleteDirectory($this->publicDir);
});

it('crops and writes webp variants not wider than the crop', function () {
    $m = Media::create(['collection' => 'cover', 'original_path' => $this->store('3000x2000.jpg'), 'crop' => ['x' => 0, 'y' => 0, 'width' => 1600, 'height' => 1000, 'rotate' => 0, 'flipX' => false, 'flipY' => false]]);
    $m->refresh();
    expect($m->width)->toBe(1600)->and($m->height)->toBe(1000)->and($m->widths)->toBe([640, 1280])
        ->and(is_file(public_path($m->key.'-1280.webp')))->toBeTrue();
    expect(getimagesize(public_path($m->key.'-640.webp'))[1])->toBe(400);
});
it('writes avif only when GD supports it', fn () => expect(in_array('avif', Media::factoryCover()->formats))->toBe(function_exists('imageavif')));
it('re-crop replaces files and key', function () {
    $m = Media::factoryCover();
    $old = $m->key;
    $m->update(['crop' => [...$m->crop, 'width' => 800, 'height' => 500]]);
    expect($m->fresh()->key)->not->toBe($old)->and(glob(public_path($old.'-*')))->toBe([]);
});
it('applies exif orientation', function () {
    $m = Media::create(['collection' => 'x', 'original_path' => $this->storeFixture('exif-rotated.jpg')]);
    expect([$m->fresh()->width, $m->fresh()->height])->toBe([300, 400]);
});
it('handles a 6000px original within 1920 output', function () {
    $m = Media::create(['collection' => 'x', 'original_path' => $this->store('6000x4000.jpg')]);
    expect(max($m->fresh()->widths))->toBe(1920);
});
it('deleting media removes its files', function () {
    $m = Media::factoryCover();
    $key = $m->key;
    $m->delete();
    expect(glob(public_path($key.'-*')))->toBe([]);
});
it('manifest serves media entries with focal', function () {
    $m = Media::factoryCover(['focal_x' => 30, 'focal_y' => 20]);
    expect(ImageManifest::get($m->key))->toMatchArray(['base' => $m->key, 'focal' => ['x' => 30, 'y' => 20]]);
});

it('keeps a narrow frame at its own width and stores the key format', function () {
    $m = Media::create(['collection' => 'x', 'original_path' => $this->store('500x400.jpg')]);
    $m->refresh();
    expect($m->widths)->toBe([500])->and($m->key)->toMatch('#^media/'.$m->id.'-[0-9a-f]{8}$#')
        ->and(getimagesize(public_path($m->key.'-500.webp'))[0])->toBe(500);
});

it('rotates and flips before cropping', function () {
    $m = Media::create(['collection' => 'x', 'original_path' => $this->store('3000x2000.jpg'),
        'crop' => ['x' => 0, 'y' => 0, 'width' => 1000, 'height' => 1500, 'rotate' => 90, 'flipX' => true, 'flipY' => false]]);
    expect([$m->fresh()->width, $m->fresh()->height])->toBe([1000, 1500]);
});

it('deleting media removes the original', function () {
    $m = Media::factoryCover();
    $path = $m->original_path;
    $m->delete();
    Storage::disk('local')->assertMissing($path);
});

it('changing only alt does not re-process', function () {
    $m = Media::factoryCover();
    $key = $m->key;
    $m->update(['alt' => 'Новый alt']);
    expect($m->fresh()->key)->toBe($key);
});

it('belongs to an owner collection ordered by sort', function () {
    $owner = MediaOwnerStub::create(['title' => 'Группа']);
    $b = Media::factoryCover(['owner_type' => $owner->getMorphClass(), 'owner_id' => $owner->id, 'collection' => 'gallery', 'sort' => 2]);
    $a = Media::factoryCover(['owner_type' => $owner->getMorphClass(), 'owner_id' => $owner->id, 'collection' => 'gallery', 'sort' => 1]);
    expect($owner->media('gallery')->pluck('id')->all())->toBe([$a->id, $b->id])
        ->and($owner->firstMedia('gallery')->id)->toBe($a->id)
        ->and($owner->firstMedia('cover'))->toBeNull();
});

// --- Сбой нарезки ---

it('an unsupported or corrupt original throws, leaving no row, no files and no original', function () {
    Storage::disk('local')->put('media-originals/broken.jpg', 'это не картинка');
    expect(fn () => Media::create(['collection' => 'x', 'original_path' => 'media-originals/broken.jpg']))
        ->toThrow(MediaProcessingFailed::class, 'Не удалось обработать фото');
    expect(Media::count())->toBe(0)
        ->and(glob(public_path('media/*')) ?: [])->toBe([]);
    Storage::disk('local')->assertMissing('media-originals/broken.jpg');
});

it('a failed re-crop keeps the previous key, crop and files', function () {
    $m = Media::factoryCover();
    $key = $m->key;
    $crop = $m->crop;
    Storage::disk('local')->put($m->original_path, 'испорчено');
    expect(fn () => $m->update(['crop' => [...$crop, 'width' => 800, 'height' => 500]]))
        ->toThrow(MediaProcessingFailed::class);
    $fresh = $m->fresh();
    expect($fresh->key)->toBe($key)->and($fresh->crop)->toBe($crop)
        ->and(is_file(public_path($key.'-1280.webp')))->toBeTrue()
        ->and(glob(public_path('media/*')))->toHaveCount(count(glob(public_path($key.'-*'))));
});

it('picture renders nothing for a media key without variants', function () {
    (new Media)->forceFill(['collection' => 'x', 'original_path' => 'media-originals/x.jpg', 'key' => 'media/9-ab12cd34', 'widths' => []])->saveQuietly();
    expect(Blade::render('<x-picture src="media/9-ab12cd34" />'))->not->toContain('-.webp')->not->toContain('<picture')
        ->and(Blade::render('<x-picture src="media/404-00000000" />'))->not->toContain('<picture');
});

// --- Порядок преобразований (как Cropper.js: отражение, затем поворот по часовой) ---
// Исходник 400×300: белый, красная метка в левом верхнем углу.

function markerOriginal(): string
{
    $im = imagecreatetruecolor(400, 300);
    imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
    imagefilledrectangle($im, 0, 0, 99, 74, imagecolorallocate($im, 255, 0, 0));
    ob_start();
    imagejpeg($im, null, 90);
    Storage::disk('local')->put($path = 'media-originals/marker-'.bin2hex(random_bytes(3)).'.jpg', ob_get_clean());

    return $path;
}

/** В каком углу варианта красная метка: TL / TR / BL / BR. */
function markerCorner(Media $m): string
{
    $m->refresh();
    $im = imagecreatefromwebp(public_path($m->key.'-'.max($m->widths).'.webp'));
    [$w, $h] = [imagesx($im), imagesy($im)];
    $red = fn ($x, $y) => (($c = imagecolorat($im, $x, $y)) >> 16 & 255) > 180 && ($c >> 8 & 255) < 90;
    $hits = array_keys(array_filter([
        'TL' => $red(10, 10), 'TR' => $red($w - 11, 10), 'BL' => $red(10, $h - 11), 'BR' => $red($w - 11, $h - 11),
    ]));

    return implode(',', $hits);
}

function markerMedia(array $crop): Media
{
    return Media::create(['collection' => 'x', 'original_path' => markerOriginal(),
        'crop' => [...['rotate' => 0, 'flipX' => false, 'flipY' => false], ...$crop]]);
}

it('rotate 90 moves the top-left marker to top-right', fn () => expect(markerCorner(markerMedia(['rotate' => 90])))->toBe('TR'));
it('flipX moves the top-left marker to top-right', fn () => expect(markerCorner(markerMedia(['flipX' => true])))->toBe('TR'));
it('flipX then rotate 90 moves the top-left marker to bottom-right', fn () => expect(markerCorner(markerMedia(['rotate' => 90, 'flipX' => true])))->toBe('BR'));
it('flipY then rotate 270 moves the top-left marker to bottom-right', fn () => expect(markerCorner(markerMedia(['rotate' => 270, 'flipY' => true])))->toBe('BR'));

it('crop rect is taken in the rotated canvas', function () {
    // После поворота на 90 холст 300×400, метка в правом верхнем углу (x 225–299, y 0–99).
    $m = markerMedia(['rotate' => 90, 'x' => 150, 'y' => 0, 'width' => 150, 'height' => 200]);
    expect(markerCorner($m))->toBe('TR')->and([$m->width, $m->height])->toBe([150, 200]);
});

it('exif orientation 6 moves the stored top-left marker to top-right', function () {
    $m = Media::create(['collection' => 'x', 'original_path' => $this->storeFixture('exif-rotated.jpg')]);
    expect(markerCorner($m))->toBe('TR');
});

it('exif orientation 8 moves the stored top-left marker to bottom-left', function () {
    $entry6 = pack('vvV', 0x0112, 3, 1).pack('vv', 6, 0);
    $bytes = file_get_contents(base_path('tests/fixtures/exif-rotated.jpg'));
    expect(substr_count($bytes, $entry6))->toBe(1);
    Storage::disk('local')->put('media-originals/exif-8.jpg', str_replace($entry6, pack('vvV', 0x0112, 3, 1).pack('vv', 8, 0), $bytes));
    $m = Media::create(['collection' => 'x', 'original_path' => 'media-originals/exif-8.jpg']);
    expect(markerCorner($m))->toBe('BL')->and([$m->width, $m->height])->toBe([300, 400]);
});
