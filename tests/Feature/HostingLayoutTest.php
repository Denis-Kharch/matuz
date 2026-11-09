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
