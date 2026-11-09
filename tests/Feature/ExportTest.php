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
    expect(is_file("{$this->out}/brand/favicon-32.png"))->toBeTrue()
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

it('starts sitemap with the xml declaration and keeps "<?" out of templates (short_open_tag on hosting)', function () {
    expect($this->get('/sitemap.xml')->getContent())->toStartWith('<?xml version="1.0" encoding="UTF-8"?>'."\n<urlset");
    foreach (File::allFiles(resource_path('views')) as $f) {
        expect(preg_match('/<\?(?!php)/', $f->getContents()))->toBe(0, $f->getRelativePathname().' содержит «<?»');
    }
});

it('robots disallows everything in preview and allows in production', function () {
    config(['site.preview' => true]);
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
    config(['site.preview' => false]);
    $this->get('/robots.txt')->assertOk()->assertDontSee('Disallow: /')->assertSee('Sitemap: '.url('sitemap.xml'));
});

it('renders a 404 page', fn () => $this->get('/404.html')->assertOk()->assertSee('Страница не найдена'));

it('does not export pages and media left in public by an admin publish', function () {
    $public = sys_get_temp_dir().'/export-public-'.bin2hex(random_bytes(4));
    $original = public_path();
    File::ensureDirectoryExists("{$public}/brand");
    File::ensureDirectoryExists("{$public}/build/assets");
    File::ensureDirectoryExists("{$public}/media");
    File::ensureDirectoryExists("{$public}/cases/db-only");
    File::put("{$public}/brand/favicon-32.png", 'png');
    File::put("{$public}/build/assets/app.js", 'js');
    File::put("{$public}/media/1-abcd-640.webp", 'webp');
    File::put("{$public}/cases/db-only/index.html", 'stale');
    File::put("{$public}/index.html", 'stale home');
    File::put("{$public}/.matuzov-pages.json", json_encode(['index.html', 'cases/db-only/index.html']));
    app()->usePublicPath($public);
    $this->withoutVite();
    try {
        expect(Artisan::call('site:export', ['--out' => $this->out]))->toBe(0)
            ->and(is_file("{$this->out}/brand/favicon-32.png"))->toBeTrue()
            ->and(is_file("{$this->out}/build/assets/app.js"))->toBeTrue()
            ->and(is_file("{$this->out}/cases/db-only/index.html"))->toBeFalse()
            ->and(is_dir("{$this->out}/media"))->toBeFalse()
            ->and(is_file("{$this->out}/.matuzov-pages.json"))->toBeFalse()
            ->and(file_get_contents("{$this->out}/index.html"))->not->toBe('stale home');
    } finally {
        app()->usePublicPath($original);
        File::deleteDirectory($public);
    }
});
