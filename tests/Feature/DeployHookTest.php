<?php

use App\Models\CaseStudy;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

/** Пути кешей optimize — во временную папку, чтобы тесты не оставляли bootstrap/cache/*.php в репозитории. */
const DEPLOY_CACHE_ENV = ['APP_CONFIG_CACHE' => 'config.php', 'APP_ROUTES_CACHE' => 'routes.php', 'APP_EVENTS_CACHE' => 'events.php'];

function setDeployEnv(string $name, ?string $value): void
{
    if ($value === null) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    } else {
        putenv("{$name}={$value}");
        $_ENV[$name] = $_SERVER[$name] = $value;
    }
}

beforeEach(function () {
    Storage::fake('local');
    // Пути кешей Laravel принимает только относительно base_path (или с «/»), поэтому временная папка — в storage.
    $this->rel = 'storage/framework/testing/deploy-'.bin2hex(random_bytes(4));
    $this->tmp = base_path($this->rel);
    mkdir($this->tmp.'/site', 0777, true);
    mkdir($this->tmp.'/cache');
    $this->originalPublic = public_path();
    app()->usePublicPath($this->tmp.'/site');
    foreach (DEPLOY_CACHE_ENV as $name => $file) {
        setDeployEnv($name, $this->rel.'/cache/'.$file);
    }
    $this->withoutVite();
    $this->token = 'tok-'.bin2hex(random_bytes(8));
    config(['site.content_source' => 'database', 'app.url' => 'https://matuzov.test', 'site.deploy_token' => $this->token]);
});

afterEach(function () {
    foreach (array_keys(DEPLOY_CACHE_ENV) as $name) {
        setDeployEnv($name, null);
    }
    app()->usePublicPath($this->originalPublic);
    File::deleteDirectory($this->tmp);
    @unlink(storage_path('framework/deploy.lock'));
});

it('404s with a wrong or empty token', function () {
    config(['site.deploy_token' => 'secret']);
    $this->post('/_deploy/nope')->assertNotFound();
    config(['site.deploy_token' => '']);
    $this->post('/_deploy/')->assertNotFound();
    $this->post('/_deploy/anything')->assertNotFound();
    config(['site.deploy_token' => null]);
    $this->post('/_deploy/anything')->assertNotFound();
    expect(User::count())->toBe(0)->and(CaseStudy::count())->toBe(0);
});

it('runs install steps, imports content, creates first admin and publishes', function () {
    $sqlite = $this->tmp.'/database.sqlite';
    config(['database.connections.sqlite.database' => $sqlite]); // соединение уже открыто — меняется только «файл базы»

    $res = $this->postJson('/_deploy/'.$this->token, ['admin_email' => 'a@b.ru', 'admin_password' => 'very-strong-1'])
        ->assertOk()->assertJson(['ok' => true, 'publish' => ['ok' => true, 'error' => null]]);

    expect(User::where('email', 'a@b.ru')->exists())->toBeTrue()
        ->and(password_verify('very-strong-1', User::first()->password))->toBeTrue()
        ->and(CaseStudy::count())->toBeGreaterThan(0)
        ->and(is_file(public_path('index.html')))->toBeTrue()
        ->and(is_file($sqlite))->toBeTrue()
        ->and(is_dir(public_path('js/filament')))->toBeTrue()
        ->and(is_file($this->tmp.'/cache/config.php'))->toBeTrue()
        ->and(is_file($this->tmp.'/cache/routes.php'))->toBeTrue()
        ->and(collect($res->json('steps'))->pluck('step')->all())
        ->toBe(['database', 'migrate', 'import', 'admin', 'filament:assets', 'optimize'])
        ->and(collect($res->json('steps'))->every(fn ($s) => $s['ok'] === true))->toBeTrue();
    // Ни пароль, ни токен не возвращаются.
    expect($res->getContent())->not->toContain('very-strong-1')->not->toContain($this->token);
});

it('does not create an admin when users exist', function () {
    User::factory()->create(['email' => 'owner@b.ru']);
    $res = $this->postJson('/_deploy/'.$this->token, ['admin_email' => 'other@b.ru', 'admin_password' => 'very-strong-2'])
        ->assertOk()->assertJson(['ok' => true]);
    expect(User::where('email', 'other@b.ru')->exists())->toBeFalse()->and(User::count())->toBe(1)
        ->and(collect($res->json('steps'))->firstWhere('step', 'admin'))->toMatchArray(['ok' => true]);
});

it('rejects bad admin credentials before doing anything', function (array $body) {
    $res = $this->postJson('/_deploy/'.$this->token, $body)->assertStatus(422)->assertJson(['ok' => false]);
    expect(User::count())->toBe(0)->and(CaseStudy::count())->toBe(0)
        ->and(is_file(public_path('index.html')))->toBeFalse()
        ->and($res->getContent())->not->toContain('short-pw');
})->with([
    'short password' => [['admin_email' => 'a@b.ru', 'admin_password' => 'short-pw']],
    'bad email' => [['admin_email' => 'not-an-email', 'admin_password' => 'very-strong-1']],
    'password without email' => [['admin_password' => 'very-strong-1']],
    'password not a string' => [['admin_email' => 'a@b.ru', 'admin_password' => ['very-strong-1']]],
]);

it('refuses a second call while an install is running and changes nothing', function () {
    File::ensureDirectoryExists(storage_path('framework'));
    $h = fopen(storage_path('framework/deploy.lock'), 'c');
    expect(flock($h, LOCK_EX | LOCK_NB))->toBeTrue();
    try {
        $this->postJson('/_deploy/'.$this->token, ['admin_email' => 'a@b.ru', 'admin_password' => 'very-strong-1'])
            ->assertStatus(423)->assertJson(['ok' => false]);
    } finally {
        flock($h, LOCK_UN);
        fclose($h);
    }
    expect(User::count())->toBe(0)->and(CaseStudy::count())->toBe(0)
        ->and(is_file(public_path('index.html')))->toBeFalse()
        ->and(is_file($this->tmp.'/cache/config.php'))->toBeFalse();
});

it('keeps PUBLIC_SITE_PATH from the env file after config:cache', function () {
    // Отдельные процессы: настоящий config:cache, затем загрузка приложения с кешем (как на хостинге).
    $env = 'deploytest'.bin2hex(random_bytes(3));
    $envFile = base_path('.env.'.$env);
    $site = $this->tmp.'/site';
    file_put_contents($envFile, 'APP_KEY=base64:'.base64_encode(random_bytes(32))."\nPUBLIC_SITE_PATH='{$site}'\n");
    $vars = ['APP_ENV' => $env, 'APP_CONFIG_CACHE' => $this->rel.'/cache/config.php', 'PUBLIC_SITE_PATH' => false];
    try {
        $cache = new Process(['php', 'artisan', 'config:cache'], base_path(), $vars);
        $cache->run();
        expect($cache->getExitCode())->toBe(0, $cache->getErrorOutput().$cache->getOutput())
            ->and(is_file($this->tmp.'/cache/config.php'))->toBeTrue();
    } finally {
        @unlink($envFile); // с кешем конфигурации .env больше не читается
    }
    $boot = new Process(['php', '-r', 'require "vendor/autoload.php"; $app = require "bootstrap/app.php";'
        .' $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();'
        .' echo json_encode([$app->configurationIsCached(), public_path(), config("site.public_path")]);'], base_path(), $vars);
    $boot->run();
    expect(json_decode($boot->getOutput(), true))->toBe([true, $site, $site], $boot->getErrorOutput().$boot->getOutput())
        ->and(is_file(base_path('bootstrap/cache/config.php')))->toBeFalse();
});

it('the commands the hook calls exist in a web request', function () {
    // На хостинге хук работает из веб-запроса: команды, зарегистрированные только для консоли, там недоступны.
    $p = new Process(['php', '-r', 'require "vendor/autoload.php"; $app = require "bootstrap/app.php";'
        .' $app->instance("request", Illuminate\Http\Request::create("/"));'
        .' $app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();'
        .' echo json_encode([$app->runningInConsole(), array_values(array_intersect(["migrate", "filament:assets", "optimize"], array_keys(Illuminate\Support\Facades\Artisan::all())))]);'],
        base_path(), ['APP_RUNNING_IN_CONSOLE' => 'false']);
    $p->run();
    expect(json_decode($p->getOutput(), true))->toBe([false, ['migrate', 'filament:assets', 'optimize']], $p->getErrorOutput().$p->getOutput());
});

it('livewire accepts uploads up to 15 MB and the hosting php limits allow them', function () {
    expect(config('livewire.temporary_file_upload.rules'))->toBe(['required', 'file', 'max:15360']);
    $ini = parse_ini_file(base_path('deploy/regru/.user.ini'));
    expect($ini)->toMatchArray(['upload_max_filesize' => '16M', 'post_max_size' => '64M', 'memory_limit' => '512M', 'max_execution_time' => '120']);
});

it('env template sets production values with placeholders', function () {
    $t = file_get_contents(base_path('deploy/regru/env.template'));
    foreach (['APP_ENV=production', 'APP_DEBUG=false', 'APP_URL=https://matuzov.ru', 'ASSET_URL=https://matuzov.ru',
        'APP_LOCALE=ru', 'DB_CONNECTION=sqlite', 'CONTENT_SOURCE=database', 'PUBLIC_SITE_PATH=__SITE_PATH__',
        'DEPLOY_TOKEN=__DEPLOY_TOKEN__', 'KP_ENDPOINT=/api/kp-lead', 'APP_KEY=__APP_KEY__', 'SESSION_DRIVER=file',
        'CACHE_STORE=file', 'QUEUE_CONNECTION=sync'] as $line) {
        expect($t)->toMatch('/^'.preg_quote($line, '/').'$/m');
    }
    expect($t)->not->toMatch('/^SITE_AUTO_PUBLISH=/m');
});

it('the deploy hook and kp endpoint are exempt from CSRF', function () {
    $m = new class(app(), app('encrypter')) extends PreventRequestForgery
    {
        public function exempt(string $uri): bool
        {
            return $this->inExceptArray(Request::create($uri, 'POST'));
        }
    };
    expect($m->exempt('/_deploy/abc'))->toBeTrue()->and($m->exempt('/api/kp-lead'))->toBeTrue()
        ->and($m->exempt('/admin/login'))->toBeFalse();
});

it('deploy workflow keeps the safety guards and pages runs only by hand', function () {
    $wf = file_get_contents(base_path('.github/workflows/deploy-regru.yml'));
    expect($wf)->toContain("grep -qx '\.matuzov-app'")->toContain("grep -qx '\.matuzov-deploy'")
        ->toContain('looks_like_root "$APP_LIST" || looks_like_root "$SITE_LIST"')
        ->toContain('ASSET_URL: ${{ env.SITE_URL }}')->toContain('npm run build')
        ->toContain("-x '^[.]env$'")->toContain("-x '^storage/'")->toContain("-x '^database/.*[.]sqlite'")
        ->toContain('/_deploy/$DEPLOY_TOKEN')->toContain('jq -n --arg e "$ADMIN_EMAIL" --arg p "$ADMIN_PASSWORD"')
        ->not->toContain('mirror --reverse --delete "$SITE/"'); // корень папки сайта не зеркалируется
    $pages = file_get_contents(base_path('.github/workflows/pages.yml'));
    expect($pages)->toContain('workflow_dispatch')->not->toMatch('/^\s+push:/m');
});
