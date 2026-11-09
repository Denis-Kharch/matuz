<?php

use Illuminate\Support\Facades\File;

require_once __DIR__.'/../../deploy/regru/unpack.php';

beforeEach(function () {
    $this->app_dir = storage_path('framework/testing/unpack-'.uniqid());
    $put = function (string $rel, string $body) {
        File::ensureDirectoryExists(dirname($this->app_dir.'/'.$rel));
        File::put($this->app_dir.'/'.$rel, $body);
    };
    // Старое приложение на сервере: код, база, .env, storage, метка.
    foreach ([
        '.env' => 'DEPLOY_TOKEN=x', '.matuzov-app' => 'marker', 'artisan' => 'old',
        'app/Old.php' => 'old', 'app/Kept.php' => 'old', 'vendor/autoload.php' => 'old', 'bootstrap/app.php' => 'old',
        'bootstrap/cache/config.php' => 'stale', 'database/database.sqlite' => 'DB', 'database/migrations/old.php' => 'old',
        'database/stale.txt' => 'old', 'storage/logs/laravel.log' => 'log',
    ] as $rel => $body) {
        $put($rel, $body);
    }
    $this->zip = function (array $files) {
        File::ensureDirectoryExists($this->app_dir.'/storage/app/deploy');
        $z = new ZipArchive;
        $z->open($this->app_dir.'/storage/app/deploy/app.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $name => $body) {
            $z->addFromString($name, $body);
        }
        $z->close();
    };
});

afterEach(fn () => File::deleteDirectory($this->app_dir));

$release = [
    'artisan' => 'new', 'app/Kept.php' => 'new', 'app/New.php' => 'new', 'vendor/autoload.php' => 'new',
    'bootstrap/app.php' => 'new', 'bootstrap/cache/.gitignore' => '*', 'database/migrations/new.php' => 'new',
];

it('swaps app folders from the archive and keeps env, database and storage', function () use ($release) {
    ($this->zip)($release);
    $r = matuzov_unpack($this->app_dir);

    $d = $this->app_dir;
    expect($r['ok'])->toBeTrue()
        ->and(file_get_contents("$d/app/Kept.php"))->toBe('new')
        ->and(file_exists("$d/app/New.php"))->toBeTrue()
        ->and(file_exists("$d/app/Old.php"))->toBeFalse()
        ->and(file_get_contents("$d/vendor/autoload.php"))->toBe('new')
        ->and(file_exists("$d/bootstrap/cache/config.php"))->toBeFalse()
        ->and(is_dir("$d/bootstrap/cache"))->toBeTrue()
        ->and(file_exists("$d/database/migrations/new.php"))->toBeTrue()
        ->and(file_exists("$d/database/migrations/old.php"))->toBeFalse()
        ->and(file_exists("$d/database/stale.txt"))->toBeFalse()
        ->and(file_get_contents("$d/database/database.sqlite"))->toBe('DB')
        ->and(file_get_contents("$d/.env"))->toBe('DEPLOY_TOKEN=x')
        ->and(file_get_contents("$d/.matuzov-app"))->toBe('marker')
        ->and(file_get_contents("$d/storage/logs/laravel.log"))->toBe('log')
        ->and(file_exists("$d/storage/app/deploy/app.zip"))->toBeFalse()
        ->and(file_exists("$d/.release-new") || file_exists("$d/.release-old"))->toBeFalse();
});

it('leaves the app untouched when the archive is incomplete', function () {
    ($this->zip)(['app/New.php' => 'new']);
    expect(fn () => matuzov_unpack($this->app_dir))->toThrow(RuntimeException::class);
    expect(file_get_contents($this->app_dir.'/app/Old.php'))->toBe('old')
        ->and(file_get_contents($this->app_dir.'/vendor/autoload.php'))->toBe('old');
});

it('rejects archive paths that escape the app folder', function () use ($release) {
    ($this->zip)([...$release, '../evil.php' => 'x']);
    expect(fn () => matuzov_unpack($this->app_dir))->toThrow(RuntimeException::class, 'Недопустимый путь');
    expect(file_exists(dirname($this->app_dir).'/evil.php'))->toBeFalse()
        ->and(file_get_contents($this->app_dir.'/app/Old.php'))->toBe('old');
});

it('fails clearly without an archive', function () {
    expect(fn () => matuzov_unpack($this->app_dir))->toThrow(RuntimeException::class, 'Нет архива');
});
