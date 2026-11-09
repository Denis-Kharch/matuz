<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
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

return $app;
