<?php

use App\Content\ContentImporter;
use App\Content\DatabaseContentRepository;
use App\Content\StaticContentRepository;
use App\Models\CaseStudy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->publicDir = sys_get_temp_dir().'/dbcontent-test-'.bin2hex(random_bytes(4));
    mkdir($this->publicDir);
    $this->originalPublic = public_path();
    app()->usePublicPath($this->publicDir);
    $this->withoutVite();
});

afterEach(function () {
    app()->usePublicPath($this->originalPublic);
    File::deleteDirectory($this->publicDir);
});

it('database repository matches static content after import', function () {
    app(ContentImporter::class)->importIfEmpty();
    $db = app(DatabaseContentRepository::class);
    $st = app(StaticContentRepository::class);
    expect($db->hero()['title'])->toBe($st->hero()['title'])
        ->and(array_column($db->cases(), 'slug'))->toBe(array_column($st->cases(), 'slug'))
        ->and($db->cases()[0]['plan_steps'])->toBe($st->cases()[0]['plan_steps'])
        ->and(count($db->events()))->toBe(count($st->events()))
        ->and($db->events()[0]['date'] >= $db->events()[1]['date'])->toBeTrue()
        ->and($db->events(2))->toHaveCount(2)
        ->and($db->formats()[2]['n'])->toBe('03')
        ->and($db->principles())->toBe($st->principles())
        ->and($db->faq())->toBe($st->faq())
        ->and($db->contacts())->toBe($st->contacts())
        ->and($db->topicGroups())->toBe($st->topicGroups());
});

it('returns the same array shapes as the static repository', function () {
    app(ContentImporter::class)->importIfEmpty();
    $db = app(DatabaseContentRepository::class);
    $st = app(StaticContentRepository::class);
    $keys = fn (array $a) => array_keys($a);
    foreach (['hero', 'about'] as $m) {
        expect($keys($db->$m()))->toEqualCanonicalizing($keys($st->$m()));
    }
    foreach (['cases', 'events', 'partners', 'certificates', 'gallery', 'experience', 'formats', 'eventServices'] as $m) {
        expect($keys($db->$m()[0]))->toEqualCanonicalizing($keys($st->$m()[0]), $m);
    }
    expect($db->hero()['image'])->toStartWith('media/')
        ->and($db->hero()['image_mobile'])->toBeNull()
        ->and($db->hero()['focal'])->toBe(['x' => 24, 'y' => 30])
        ->and($db->about()['photo'])->toStartWith('media/')
        ->and($db->cases()[0]['cover'])->toStartWith('media/')
        ->and($db->cases()[0]['photos'])->toHaveCount(3)
        ->and($db->events()[0]['photos'][0])->toStartWith('media/')
        ->and($db->partners()[0]['logo'])->toBeNull()
        ->and($db->gallery()[0]['image'])->toStartWith('media/')
        ->and($db->findCase($st->cases()[1]['slug'])['title'])->toBe($st->cases()[1]['title'])
        ->and($db->findEvent('no-such'))->toBeNull();
});

it('hidden items do not appear on the site', function () {
    app(ContentImporter::class)->importIfEmpty();
    config(['site.content_source' => 'database']);
    $c = CaseStudy::first();
    // Название кейса входит и в заголовок события-практикума, поэтому ищем ссылку на кейс, а не текст.
    $this->get('/')->assertOk()->assertSee('cases/'.$c->slug);
    $c->update(['is_visible' => false]);
    $this->get('/')->assertOk()->assertDontSee('cases/'.$c->slug)->assertDontSee($c->title.'</h3>', false);
    $this->get('/cases/'.$c->slug)->assertNotFound();
});

it('site renders fully from the database', function () {
    app(ContentImporter::class)->importIfEmpty();
    config(['site.content_source' => 'database']);
    $html = $this->get('/')->assertOk()->assertSee('Реальная практика, подтверждённая результатами')->getContent();
    expect($html)->toContain('/media/')->not->toContain('-.webp')->not->toContain('img/media/');
    $this->get('/events')->assertOk();
});

it('database rich text is sanitized on the page', function () {
    app(ContentImporter::class)->importIfEmpty();
    config(['site.content_source' => 'database']);
    $c = CaseStudy::first();
    $c->update(['result' => '<p>Итог <b>ок</b><script>alert(1)</script></p>']);
    $this->get('/cases/'.$c->slug)->assertOk()->assertSee('<b>ок</b>', false)->assertDontSee('<script>alert(1)', false);
});
