<?php

use App\Filament\Forms\ImageField;
use App\Filament\Resources\CaseStudies\CaseStudyResource;
use App\Filament\Resources\CaseStudies\Pages\EditCaseStudy;
use App\Models\CaseStudy;
use App\Models\Media;
use App\Models\Partner;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\TestImageForm;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->publicDir = sys_get_temp_dir().'/image-field-test-'.bin2hex(random_bytes(4));
    mkdir($this->publicDir);
    $this->originalPublic = public_path();
    app()->usePublicPath($this->publicDir);
    $this->withoutVite();
    TestImageForm::$components = null;
});

afterEach(function () {
    TestImageForm::$components = null;
    app()->usePublicPath($this->originalPublic);
    File::deleteDirectory($this->publicDir);
});

function photoItem(array $overrides = []): array
{
    return [...['id' => null, 'upload' => null, 'crop' => null, 'focal_x' => 50, 'focal_y' => 50, 'caption' => null, 'alt' => null], ...$overrides];
}

/** Ключ элемента списка с указанным id записи Media. */
function itemKey($lw, string $field, int $mediaId): string
{
    return collect($lw->get("data.{$field}"))->search(fn ($item) => (int) ($item['id'] ?? 0) === $mediaId);
}

it('saves crop, focal, caption and order for a multiple field', function () {
    $case = CaseStudy::factory()->create();
    $this->actingAs(User::factory()->create());

    Livewire::test(TestImageForm::class, ['record' => $case])
        ->set('data.photos', [
            'b' => photoItem(['crop' => null, 'focal_x' => 50, 'focal_y' => 50, 'caption' => 'B', 'alt' => 'b']),
            'a' => photoItem(['crop' => ['x' => 0, 'y' => 0, 'width' => 800, 'height' => 600, 'rotate' => 90, 'flipX' => false, 'flipY' => false], 'focal_x' => 20, 'focal_y' => 30, 'caption' => 'A', 'alt' => 'a']),
        ])
        ->set('data.photos.b.upload', [UploadedFile::fake()->image('b.jpg', 1600, 1200)])
        ->set('data.photos.a.upload', [UploadedFile::fake()->image('a.jpg', 1600, 1200)])
        // Загрузка сбрасывает кадр и фокус (новое фото) — задаём их после неё, как в интерфейсе.
        ->set('data.photos.a.crop', ['x' => 0, 'y' => 0, 'width' => 800, 'height' => 600, 'rotate' => 90, 'flipX' => false, 'flipY' => false])
        ->set('data.photos.a.focal_x', 20)
        ->set('data.photos.a.focal_y', 30)
        ->call('save')
        ->assertHasNoErrors();

    $m = $case->media('photos')->get();
    expect($m->pluck('caption')->all())->toBe(['B', 'A'])
        ->and($m->pluck('alt')->all())->toBe(['b', 'a'])
        ->and($m[1]->crop['rotate'])->toBe(90)
        ->and($m[1]->focal_x)->toBe(20)
        ->and($m[1]->focal_y)->toBe(30)
        ->and($m[0]->crop)->toBeNull()
        // Поворот на 90° кадра 800×600 → вариант 800 в ширину не шире кадра.
        ->and($m[1]->width)->toBe(800)
        ->and($m[1]->key)->toStartWith('media/')
        ->and(Storage::disk('local')->exists($m[1]->original_path))->toBeTrue()
        ->and($m[1]->original_path)->toStartWith('media-originals/');
});

it('loads existing photos, reorders and deletes them', function () {
    $case = CaseStudy::factory()->create();
    $one = Media::factory()->create(['owner_type' => $case->getMorphClass(), 'owner_id' => $case->id, 'collection' => 'photos', 'caption' => 'Один', 'sort' => 1]);
    $two = Media::factory()->create(['owner_type' => $case->getMorphClass(), 'owner_id' => $case->id, 'collection' => 'photos', 'caption' => 'Два', 'sort' => 2]);
    $three = Media::factory()->create(['owner_type' => $case->getMorphClass(), 'owner_id' => $case->id, 'collection' => 'photos', 'caption' => 'Три', 'sort' => 3]);
    $this->actingAs(User::factory()->create());

    $lw = Livewire::test(TestImageForm::class, ['record' => $case]);
    $items = $lw->get('data.photos');
    expect(collect($items)->pluck('caption')->values()->all())->toBe(['Один', 'Два', 'Три']);

    $k1 = itemKey($lw, 'photos', $one->id);
    $k3 = itemKey($lw, 'photos', $three->id);
    $lw->set('data.photos', [$k3 => $items[$k3], $k1 => [...$items[$k1], 'caption' => 'Первый']])
        ->call('save')
        ->assertHasNoErrors();

    expect($case->media('photos')->pluck('caption')->all())->toBe(['Три', 'Первый'])
        ->and(Media::find($two->id))->toBeNull()
        ->and(Storage::disk('local')->exists($two->original_path))->toBeFalse();
});

it('replacing an upload keeps caption and alt', function () {
    $case = CaseStudy::factory()->create();
    $media = Media::factory()->create([
        'owner_type' => $case->getMorphClass(), 'owner_id' => $case->id, 'collection' => 'photos',
        'caption' => 'Тренинг', 'alt' => 'Участники в зале',
        'crop' => ['x' => 0, 'y' => 0, 'width' => 400, 'height' => 300, 'rotate' => 0, 'flipX' => false, 'flipY' => false],
    ])->refresh();
    $oldPath = $media->original_path;
    $oldKey = $media->key;
    $this->actingAs(User::factory()->create());

    $lw = Livewire::test(TestImageForm::class, ['record' => $case]);
    $key = itemKey($lw, 'photos', $media->id);
    $lw->set("data.photos.{$key}.upload", [UploadedFile::fake()->image('new.jpg', 1200, 900)])
        ->assertSet("data.photos.{$key}.crop", null)
        ->call('save')
        ->assertHasNoErrors();

    $media->refresh();
    expect(Media::count())->toBe(1)
        ->and($media->caption)->toBe('Тренинг')
        ->and($media->alt)->toBe('Участники в зале')
        ->and($media->original_path)->not->toBe($oldPath)
        ->and($media->crop)->toBeNull()
        ->and($media->width)->toBe(1200)
        ->and($media->key)->not->toBe($oldKey)
        ->and(Storage::disk('local')->exists($oldPath))->toBeFalse()
        ->and(Storage::disk('local')->exists($media->original_path))->toBeTrue();
});

it('rejects HEIC with a clear message', function () {
    $case = CaseStudy::factory()->create();
    $this->actingAs(User::factory()->create());

    Livewire::test(TestImageForm::class, ['record' => $case])
        ->set('data.photos', ['a' => photoItem()])
        ->set('data.photos.a.upload', [UploadedFile::fake()->create('IMG_0001.HEIC', 200, 'image/heic')])
        ->assertHasErrors(['data.photos.a.upload'])
        ->assertSee('HEIC');

    expect(Media::count())->toBe(0);
});

it('rejects a file that is not an image', function () {
    $case = CaseStudy::factory()->create();
    $this->actingAs(User::factory()->create());

    Livewire::test(TestImageForm::class, ['record' => $case])
        ->set('data.photos', ['a' => photoItem()])
        ->set('data.photos.a.upload', [UploadedFile::fake()->create('photo.jpg', 20, 'image/jpeg')])
        ->call('save')
        ->assertHasErrors(['data.photos.a.upload']);

    expect(Media::count())->toBe(0);
});

it('contain field keeps a transparent png uncropped and rejects svg with a clear message', function () {
    TestImageForm::$components = fn () => [ImageField::make('logo')->contain()];
    $partner = Partner::create(['name' => 'Ромашка', 'short' => 'Р', 'description' => '', 'url' => null]);
    $this->actingAs(User::factory()->create());

    Livewire::test(TestImageForm::class, ['record' => $partner])
        ->set('data.logo', ['a' => photoItem()])
        ->set('data.logo.a.upload', [UploadedFile::fake()->create('logo.svg', 2, 'image/svg+xml')])
        ->assertHasErrors(['data.logo.a.upload'])
        ->assertSee('PNG')
        ->set('data.logo.a.upload', [UploadedFile::fake()->image('logo.png', 400, 200)])
        ->set('data.logo.a.crop', ['x' => 0, 'y' => 0, 'width' => 10, 'height' => 10, 'rotate' => 0, 'flipX' => false, 'flipY' => false])
        ->call('save')
        ->assertHasNoErrors();

    $logo = $partner->firstMedia('logo');
    expect($logo->crop)->toBeNull()->and($logo->width)->toBe(400);
});

it('slot mode saves a settings photo without owner', function () {
    TestImageForm::$components = fn () => [ImageField::slot('hero')->aspect(16 / 9)->focal()];
    $this->actingAs(User::factory()->create());

    Livewire::test(TestImageForm::class)
        ->set('data.hero', ['a' => photoItem(['caption' => 'Андрей'])])
        ->set('data.hero.a.upload', [UploadedFile::fake()->image('hero.jpg', 1920, 1080)])
        ->set('data.hero.a.focal_x', 70)
        ->call('save')
        ->assertHasNoErrors();

    $hero = Media::query()->where('slot', 'hero')->sole();
    expect($hero->owner_type)->toBeNull()->and($hero->collection)->toBe('default')->and($hero->focal_x)->toBe(70);

    // Повторное открытие показывает сохранённое фото; пустой список удаляет его.
    $lw = Livewire::test(TestImageForm::class);
    expect(collect($lw->get('data.hero'))->first()['id'])->toBe($hero->id);
    $lw->set('data.hero', [])->call('save')->assertHasNoErrors();
    expect(Media::count())->toBe(0);
});

it('ignores media ids that belong to another owner', function () {
    $case = CaseStudy::factory()->create();
    $other = CaseStudy::factory()->create();
    $foreign = Media::factory()->create(['owner_type' => $other->getMorphClass(), 'owner_id' => $other->id, 'collection' => 'photos', 'caption' => 'Чужое']);
    $this->actingAs(User::factory()->create());

    Livewire::test(TestImageForm::class, ['record' => $case])
        ->set('data.photos', ['x' => photoItem(['id' => $foreign->id, 'caption' => 'Взломано'])])
        ->call('save');

    expect($foreign->refresh()->caption)->toBe('Чужое')->and($case->media('photos')->count())->toBe(0);
});

it('original image route requires login', fn () => $this->get('/admin/media/1/original')->assertRedirect('/admin/login'));

it('original image route streams the original for a logged-in user', function () {
    $media = Media::factory()->create();
    $this->actingAs(User::factory()->create())
        ->get("/admin/media/{$media->id}/original")
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});

it('adds several photos at once and replaces one through the item action', function () {
    $case = CaseStudy::factory()->create();
    $this->actingAs(User::factory()->create());

    $lw = Livewire::test(TestImageForm::class, ['record' => $case])
        ->callAction(TestAction::make('add')->schemaComponent('photos'), data: [
            'files' => [UploadedFile::fake()->image('1.jpg', 800, 600), UploadedFile::fake()->image('2.jpg', 800, 600)],
        ])
        ->assertHasNoActionErrors();
    expect($lw->get('data.photos'))->toHaveCount(2);
    $lw->call('save')->assertHasNoErrors();
    expect($case->media('photos')->count())->toBe(2);

    $lw = Livewire::test(TestImageForm::class, ['record' => $case]);
    $first = $case->media('photos')->first();
    $key = itemKey($lw, 'photos', $first->id);
    $lw->set("data.photos.{$key}.caption", 'Сохранится')
        ->callAction(TestAction::make('replace')->schemaComponent('photos')->arguments(['item' => $key]), data: [
            'file' => UploadedFile::fake()->image('3.jpg', 1000, 500),
        ])
        ->assertHasNoActionErrors()
        ->call('save')
        ->assertHasNoErrors();

    expect($first->refresh()->width)->toBe(1000)->and($first->caption)->toBe('Сохранится')->and($case->media('photos')->count())->toBe(2);
});

it('the add dialog rejects HEIC before creating items', function () {
    $case = CaseStudy::factory()->create();
    $this->actingAs(User::factory()->create());

    Livewire::test(TestImageForm::class, ['record' => $case])
        ->callAction(TestAction::make('add')->schemaComponent('photos'), data: [
            'files' => [UploadedFile::fake()->create('IMG_1.heic', 100, 'image/heic')],
        ])
        ->assertHasActionErrors(['files'])
        ->assertSet('data.photos', []);
});

it('case edit page shows the photo field and saves a crop through EditRecord', function () {
    $case = CaseStudy::factory()->create();
    $media = Media::factory()->create(['owner_type' => $case->getMorphClass(), 'owner_id' => $case->id, 'collection' => 'photos'])->refresh();
    $this->actingAs(User::factory()->create());

    $html = $this->get(CaseStudyResource::getUrl('edit', ['record' => $case]))
        ->assertOk()
        ->assertSee('Кадрировать')
        ->getContent();
    // Адрес оригинала для окна кадрирования (внутри экранированного JSON-конфига Alpine).
    expect($html)->toMatch('#admin\W+media\W+'.$media->id.'\W+original#');

    $lw = Livewire::test(EditCaseStudy::class, ['record' => $case->getRouteKey()]);
    $key = itemKey($lw, 'photos', $media->id);
    $lw->set("data.photos.{$key}.crop", ['x' => 0, 'y' => 0, 'width' => 600, 'height' => 450, 'rotate' => 90, 'flipX' => false, 'flipY' => false])
        ->call('save')
        ->assertHasNoErrors();

    expect($media->refresh()->crop['rotate'])->toBe(90)->and($media->width)->toBe(600)->and($case->refresh()->title)->toBe($case->title);
});
