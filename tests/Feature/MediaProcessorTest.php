<?php

use App\Models\Concerns\HasMedia;
use App\Models\Media;
use App\Support\ImageManifest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
