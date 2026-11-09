<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Без CSRF: заявки со статичных страниц и хук установки из CI (защищён токеном).
        $middleware->validateCsrfTokens(except: ['api/kp-lead', '_deploy/*']);
        // За прокси хостинга берём реальный IP посетителя из X-Forwarded-For — но только от своих адресов, не от '*'.
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', 'fc00::/7'],
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// На хостинге папка сайта (статика + app.php) лежит отдельно от приложения.
// Реальная переменная окружения действует сразу, значение из .env — после его загрузки.
if ($publicSite = getenv('PUBLIC_SITE_PATH')) {
    $app->usePublicPath($publicSite);
}
$app->afterBootstrapping(LoadEnvironmentVariables::class, function (Application $app): void {
    if ($publicSite = env('PUBLIC_SITE_PATH')) {
        $app->usePublicPath($publicSite);
    }
});
// С кешем конфигурации (optimize на хостинге) .env не читается — путь берём из закешированного site.public_path.
$app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
    if (is_string($publicSite = $app['config']->get('site.public_path')) && $publicSite !== '') {
        $app->usePublicPath($publicSite);
    }
});

return $app;
