<?php

use App\Content\ContentRepository;
use App\Http\Controllers\PageController;
use App\Site\Indexing;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/cases/{slug}', [PageController::class, 'case'])->name('cases.show');
Route::get('/events', [PageController::class, 'events'])->name('events.index');
Route::get('/events/{slug}', [PageController::class, 'event'])->name('events.show');

// Страница 404 отдельным файлом — для статичной выгрузки (GitHub Pages показывает /404.html).
Route::get('/404.html', fn () => view('errors.404'));

Route::get('/sitemap.xml', function (ContentRepository $c) {
    $urls = [
        route('home'),
        route('events.index'),
        ...array_map(fn ($case) => route('cases.show', $case['slug']), $c->cases()),
        ...array_map(fn ($e) => route('events.show', $e['slug']), $c->events()),
    ];

    return response()->view('seo.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
});

Route::get('/robots.txt', function () {
    $body = Indexing::closed()
        ? "User-agent: *\nDisallow: /\n"
        : "User-agent: *\nAllow: /\n\nSitemap: ".url('sitemap.xml')."\n";

    return response($body)->header('Content-Type', 'text/plain; charset=UTF-8');
});
