<?php

it('uses PUBLIC_SITE_PATH as public path when set', function () {
    // Путь задаётся при загрузке приложения, поэтому проверяем в отдельном процессе.
    $dir = storage_path('framework/testing/site-'.uniqid());
    mkdir($dir, 0777, true);
    $script = $dir.'/boot.php';
    file_put_contents($script, '<?php chdir('.var_export(base_path(), true).'); require "vendor/autoload.php"; '
        .'putenv('.var_export('PUBLIC_SITE_PATH='.$dir, true).'); $app = require "bootstrap/app.php"; echo $app->publicPath();');
    $out = shell_exec('php '.escapeshellarg($script));
    expect(trim($out))->toBe($dir);
    unlink($script);
    rmdir($dir);
});

it('htaccess routes only service paths to app.php', function () {
    $h = file_get_contents(base_path('deploy/regru/.htaccess'));
    expect($h)->toContain('RewriteRule ^(admin|livewire|api/kp-lead|_deploy)(/.*)?$ app.php [L,QSA]')
        ->toMatch('/RewriteCond %\{REQUEST_FILENAME\} !-f/')
        ->toContain('RewriteRule ^app\.php$ - [L]');
    // RewriteCond действует только на следующее правило.
    expect($h)->toMatch('/RewriteCond %\{REQUEST_FILENAME\} !-f\R\s*RewriteRule \^\(admin\|livewire\|api\/kp-lead\|_deploy\)/');
    expect(strpos($h, 'app.php [L,QSA]'))->toBeLessThan(strpos($h, '$1/index.html'));
});

it('front controller boots the app from APP_PATH placeholder', function () {
    $f = file_get_contents(base_path('deploy/regru/app.php'));
    expect($f)->toContain('__APP_PATH__')->toContain('/vendor/autoload.php')->toContain('/bootstrap/app.php')->toContain('handleRequest');
});

it('exposes kp endpoint meta only when configured', function () {
    config(['site.kp_endpoint' => null]);
    $this->get('/')->assertDontSee('name="kp-endpoint"', false);
    config(['site.kp_endpoint' => '/api/kp-lead']);
    $this->get('/')->assertSee('<meta name="kp-endpoint" content="'.url('/api/kp-lead').'">', false);
});

it('picks PUBLIC_SITE_PATH up from the env file after it is loaded', function () {
    $dir = storage_path('framework/testing/envsite-'.uniqid());
    mkdir($dir, 0777, true);
    file_put_contents($dir.'/.env', "PUBLIC_SITE_PATH='".$dir."/site'"."\n");
    $script = $dir.'/boot.php';
    file_put_contents($script, '<?php chdir('.var_export(base_path(), true).'); require "vendor/autoload.php"; '
        .'putenv("PUBLIC_SITE_PATH"); unset($_ENV["PUBLIC_SITE_PATH"], $_SERVER["PUBLIC_SITE_PATH"]); '
        .'$app = require "bootstrap/app.php"; $app->useEnvironmentPath('.var_export($dir, true).'); '
        .'$app->bootstrapWith([Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class]); echo $app->publicPath();');
    $out = shell_exec('php '.escapeshellarg($script));
    expect(trim($out))->toBe($dir.'/site');
    unlink($script);
    unlink($dir.'/.env');
    rmdir($dir);
});
