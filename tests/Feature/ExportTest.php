<?php

use App\Content\ContentRepository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->out = storage_path('framework/testing/export-'.uniqid());
});

afterEach(fn () => File::deleteDirectory($this->out));

it('exports every page as static html', function () {
    $c = app(ContentRepository::class);
    expect(Artisan::call('site:export', ['--out' => $this->out]))->toBe(0);

    foreach (['index.html', 'events/index.html', '404.html', 'sitemap.xml', 'robots.txt', '.nojekyll'] as $file) {
        expect(is_file("{$this->out}/{$file}"))->toBeTrue("missing {$file}");
    }
    foreach ($c->cases() as $case) {
        expect(is_file("{$this->out}/cases/{$case['slug']}/index.html"))->toBeTrue();
    }
    foreach ($c->events() as $event) {
        expect(is_file("{$this->out}/events/{$event['slug']}/index.html"))->toBeTrue();
    }
    expect(file_get_contents("{$this->out}/index.html"))->toContain('Реальная практика, подтверждённая результатами');
});

it('copies public assets but not server files', function () {
    Artisan::call('site:export', ['--out' => $this->out]);
    expect(is_file("{$this->out}/favicon.svg"))->toBeTrue()
        ->and(is_dir("{$this->out}/build"))->toBeTrue()
        ->and(is_file("{$this->out}/img/manifest.json"))->toBeTrue()
        ->and(is_file("{$this->out}/index.php"))->toBeFalse()
        ->and(is_file("{$this->out}/.htaccess"))->toBeFalse()
        ->and(is_file("{$this->out}/hot"))->toBeFalse();
});

it('serves sitemap with all pages', function () {
    $c = app(ContentRepository::class);
    $r = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');
    $r->assertSee(route('home'), false)->assertSee(route('events.index'), false);
    foreach ($c->cases() as $case) {
        $r->assertSee(route('cases.show', $case['slug']), false);
    }
});

it('robots disallows everything in preview and allows in production', function () {
    config(['site.preview' => true]);
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
    config(['site.preview' => false]);
    $this->get('/robots.txt')->assertOk()->assertDontSee('Disallow: /')->assertSee('Sitemap: '.url('sitemap.xml'));
});

it('renders a 404 page', fn () => $this->get('/404.html')->assertOk()->assertSee('Страница не найдена'));
