<?php

use App\Content\ContentImporter;
use App\Models\CaseStudy;
use App\Models\FaqItem;
use App\Models\Setting;
use App\Site\PublishQueue;
use App\Site\PublishResult;
use App\Site\SiteBuilder;
use App\Site\SitePublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->publicDir = sys_get_temp_dir().'/publisher-test-'.bin2hex(random_bytes(4));
    mkdir($this->publicDir);
    $this->originalPublic = public_path();
    app()->usePublicPath($this->publicDir);
    $this->withoutVite();
    config(['site.content_source' => 'database', 'app.url' => 'https://matuzov.test']);
    app(ContentImporter::class)->importIfEmpty();
});

afterEach(function () {
    app()->usePublicPath($this->originalPublic);
    File::deleteDirectory($this->publicDir);
});

/** Все файлы папки сайта (относительные пути) с содержимым. */
function siteFiles(): array
{
    $files = [];
    foreach (File::allFiles(public_path(), true) as $f) {
        $files[str_replace('\\', '/', $f->getRelativePathname())] = md5_file($f->getPathname());
    }
    ksort($files);

    return $files;
}

it('publishes pages and records the page list', function () {
    $r = app(SitePublisher::class)->publish();
    expect($r->ok)->toBeTrue()->and(is_file(public_path('index.html')))->toBeTrue()
        ->and(json_decode(file_get_contents(public_path('.matuzov-pages.json')), true))->toContain('index.html');
});

it('writes every page, sitemap, robots and 404 and records the status', function () {
    $r = app(SitePublisher::class)->publish();
    $pages = json_decode(file_get_contents(public_path('.matuzov-pages.json')), true);
    foreach (['index.html', 'events/index.html', '404.html', 'sitemap.xml', 'robots.txt'] as $file) {
        expect(is_file(public_path($file)))->toBeTrue($file)->and($pages)->toContain($file);
    }
    foreach (CaseStudy::visible()->pluck('slug') as $slug) {
        expect(is_file(public_path("cases/{$slug}/index.html")))->toBeTrue($slug);
    }
    expect($r->pages)->toBe(count($pages))
        ->and(file_get_contents(public_path('sitemap.xml')))->toContain('https://matuzov.test/cases/')
        ->and(Setting::get('publish_status'))->toMatchArray(['ok' => true, 'error' => null, 'pages' => $r->pages])
        ->and(glob(public_path('*.tmp-*')))->toBe([]);
});

it('overwrites existing pages on a second publish', function () {
    $p = app(SitePublisher::class);
    $p->publish();
    Setting::put('hero', ['title' => 'Новый заголовок первого экрана'] + Setting::get('hero'));
    expect($p->publish()->ok)->toBeTrue()
        // Заголовок первого экрана разбит на слова для анимации — сверяем текст без тегов.
        ->and(strip_tags(file_get_contents(public_path('index.html'))))->toContain('Новый заголовок первого экрана');
});

it('removes the page of a deleted case and keeps media and build', function () {
    app(SitePublisher::class)->publish();
    touch(public_path('media/keep.webp')); @mkdir(public_path('build')); touch(public_path('build/keep.js'));
    $c = CaseStudy::first(); $c->delete(); app(SitePublisher::class)->publish();
    expect(is_file(public_path("cases/{$c->slug}/index.html")))->toBeFalse()
        ->and(is_dir(public_path("cases/{$c->slug}")))->toBeFalse()
        ->and(is_file(public_path('media/keep.webp')))->toBeTrue()->and(is_file(public_path('build/keep.js')))->toBeTrue();
});

it('deletes only files from the previous page list', function () {
    app(SitePublisher::class)->publish();
    File::put(public_path('cases/own.html'), 'x');
    File::put(public_path('app.php'), '<?php');
    File::put(public_path('.htaccess'), 'x');
    // Подложенный список не может удалить чужие файлы или выйти за папку сайта.
    File::put(public_path('.matuzov-pages.json'), json_encode(['app.php', '.htaccess', 'media/keep.webp', '../outside.txt', 'old/index.html']));
    File::put(public_path('media/keep.webp'), 'x');
    File::ensureDirectoryExists(public_path('old'));
    File::put(public_path('old/index.html'), 'x');
    File::put(dirname(public_path()).'/outside.txt', 'x');
    try {
        expect(app(SitePublisher::class)->publish()->ok)->toBeTrue()
            ->and(is_file(public_path('cases/own.html')))->toBeTrue()
            ->and(is_file(public_path('app.php')))->toBeTrue()
            ->and(is_file(public_path('.htaccess')))->toBeTrue()
            ->and(is_file(public_path('media/keep.webp')))->toBeTrue()
            ->and(is_file(dirname(public_path()).'/outside.txt'))->toBeTrue()
            ->and(is_file(public_path('old/index.html')))->toBeFalse();
    } finally {
        @unlink(dirname(public_path()).'/outside.txt');
    }
});

it('renamed slug replaces the old page', function () {
    app(SitePublisher::class)->publish(); $c = CaseStudy::first(); $old = $c->slug;
    $c->update(['slug' => 'novyy-slug']); app(SitePublisher::class)->publish();
    expect(is_file(public_path("cases/{$old}/index.html")))->toBeFalse()->and(is_file(public_path('cases/novyy-slug/index.html')))->toBeTrue()
        ->and(file_get_contents(public_path('index.html')))->toContain('cases/novyy-slug');
});

it('a failing render changes no files', function () {
    app(SitePublisher::class)->publish(); $before = siteFiles();
    // Страница кейса со слешем в адресе не находится маршрутом → 404 → рендер падает.
    CaseStudy::first()->update(['slug' => 'bad/slug']);
    $r = app(SitePublisher::class)->publish();
    expect($r->ok)->toBeFalse()->and($r->error)->toContain('/cases/bad/slug')->and($r->error)->toContain('404')
        ->and(siteFiles())->toBe($before)
        ->and(Setting::get('publish_status'))->toMatchArray(['ok' => false, 'error' => $r->error]);
});

it('a page with a local address fails the check and changes no files', function () {
    app(SitePublisher::class)->publish(); $before = siteFiles();
    config(['app.url' => 'http://localhost:8000']);
    $r = app(SitePublisher::class)->publish();
    expect($r->ok)->toBeFalse()->and($r->error)->toContain('localhost')
        ->and(siteFiles())->toBe($before)
        ->and(Setting::get('publish_status')['ok'])->toBeFalse();
});

it('a request during a running publish triggers one more run', function () {
    $p = app(SitePublisher::class); $lock = Cache::lock('site-publish', 120); $lock->get();
    $p->publishNow(); // занято → только флаг
    expect(Cache::get('site-publish:again'))->toBeTrue();
    $lock->release(); $p->publishNow();
    expect(Cache::get('site-publish:again'))->toBeNull()->and(Setting::get('publish_status')['ok'])->toBeTrue();
});

it('the holder repeats the publish while the again flag is raised', function () {
    $runs = 0;
    app()->instance(SiteBuilder::class, new class($runs) extends SiteBuilder
    {
        public function __construct(private int &$runs) {}

        public function render(): array
        {
            // Пока идёт первая публикация, приходит ещё один запрос.
            if (++$this->runs === 1) {
                Cache::put('site-publish:again', true);
            }

            return parent::render();
        }
    });
    expect(app(SitePublisher::class)->publishNow()->ok)->toBeTrue()
        ->and($runs)->toBe(2)->and(Cache::get('site-publish:again'))->toBeNull()
        ->and(Cache::lock('site-publish', 1)->get())->toBeTrue();
});

it('a request between the last check and the lock release is not lost', function () {
    $p = new class(app(SiteBuilder::class)) extends SitePublisher
    {
        public int $runs = 0;

        protected function publishWhileRequested(): PublishResult
        {
            $result = parent::publishWhileRequested();
            // Флаг уже проверен, блокировка ещё не отпущена: в этот момент сохраняет другой запрос.
            if (++$this->runs === 1) {
                FaqItem::create(['question' => 'Позднее сохранение', 'answer' => 'a']);
                expect((new SitePublisher(app(SiteBuilder::class)))->publishNow())->toBeNull();
            }

            return $result;
        }
    };
    $r = $p->publishNow();
    expect($p->runs)->toBe(2)->and($r->ok)->toBeTrue()
        ->and(Cache::get('site-publish:again'))->toBeNull()
        ->and(file_get_contents(public_path('index.html')))->toContain('Позднее сохранение')
        ->and(Setting::get('publish_status')['at'])->toBe($r->at->toIso8601String())
        ->and(Cache::lock('site-publish', 1)->get())->toBeTrue();
});

it('writing the publish status does not request another publish', function () {
    $q = Mockery::spy(PublishQueue::class);
    app()->instance(PublishQueue::class, $q);
    app(SitePublisher::class)->publish();
    expect(Setting::get('publish_status')['ok'])->toBeTrue();
    $q->shouldNotHaveReceived('request');
});

it('coalesces requests into one publish after the response', function () {
    $runs = 0;
    app()->instance(SiteBuilder::class, new class($runs) extends SiteBuilder
    {
        public function __construct(private int &$runs) {}

        public function render(): array
        {
            $this->runs++;

            return parent::render();
        }
    });
    config(['site.auto_publish' => true]);
    expect(app(PublishQueue::class))->toBeInstanceOf(SitePublisher::class);

    FaqItem::create(['question' => 'Q1', 'answer' => 'a']);
    FaqItem::create(['question' => 'Q2', 'answer' => 'b']);
    FaqItem::first()->delete();
    expect($runs)->toBe(0)->and(is_file(public_path('index.html')))->toBeFalse();

    app()->terminate(); // конец запроса
    app()->terminate(); // повторный вызов колбэков не публикует ещё раз
    expect($runs)->toBe(1)->and(Setting::get('publish_status')['ok'])->toBeTrue()
        ->and(file_get_contents(public_path('index.html')))->toContain('Q2');

    FaqItem::create(['question' => 'Q3', 'answer' => 'c']);
    app()->terminate();
    expect($runs)->toBe(2);
});

it('keeps the null queue when auto publish is off', function () {
    config(['site.auto_publish' => false]);
    expect(app(PublishQueue::class))->not->toBeInstanceOf(SitePublisher::class);
});

it('rendering does not disturb the current session', function () {
    $session = app('session.store');
    $session->start();
    $session->put('admin', 'kept');
    $session->flash('notice', 'saved');
    $id = $session->getId();
    app(SiteBuilder::class)->render();
    expect($session->getId())->toBe($id)->and($session->get('admin'))->toBe('kept')
        ->and($session->get('notice'))->toBe('saved')->and(request()->path())->toBe('/');
});

it('site:publish publishes and prints the result', function () {
    expect(Artisan::call('site:publish'))->toBe(0)
        ->and(Artisan::output())->toContain((string) Setting::get('publish_status')['pages'])
        ->and(is_file(public_path('index.html')))->toBeTrue();
});

it('site:publish fails on a broken render', function () {
    CaseStudy::first()->update(['slug' => 'bad/slug']);
    expect(Artisan::call('site:publish'))->toBe(1)->and(Artisan::output())->toContain('/cases/bad/slug');
});
