<?php

namespace App\Site;

use App\Content\ContentRepository;
use App\Support\ImageManifest;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * Рендер публичного сайта в набор файлов: каждая страница — через HTTP-ядро,
 * ссылки от APP_URL (может содержать подпапку).
 */
class SiteBuilder
{
    /** @return list<string> */
    public function paths(): array
    {
        $content = app(ContentRepository::class);

        return [
            '/',
            '/events',
            ...array_map(fn ($c) => '/cases/'.$c['slug'], $content->cases()),
            ...array_map(fn ($e) => '/events/'.$e['slug'], $content->events()),
            '/404.html',
            '/sitemap.xml',
            '/robots.txt',
        ];
    }

    /**
     * @return array<string, string> относительный файл → содержимое
     *
     * @throws RuntimeException если страница ответила не 200
     */
    public function render(): array
    {
        // Фото могли поменяться раньше в этом же процессе.
        ImageManifest::flush();

        $root = rtrim(config('app.url'), '/');
        $originalRequest = app('request');
        $session = app()->resolved('session.store') ? app('session.store') : null;
        $sessionState = $session?->isStarted() ? [$session->getId(), $session->all()] : null;

        URL::forceRootUrl($root);
        URL::forceScheme(str_starts_with($root, 'https://') ? 'https' : null);
        try {
            $kernel = app(Kernel::class);
            $files = [];
            foreach ($this->paths() as $path) {
                $response = $kernel->handle(Request::create($path, 'GET'));
                if ($response->getStatusCode() !== 200) {
                    $reason = isset($response->exception) ? ': '.$response->exception->getMessage() : '';

                    throw new RuntimeException("{$path}: HTTP {$response->getStatusCode()}{$reason}");
                }
                $files[$this->fileFor($path)] = $response->getContent();
            }

            return $files;
        } finally {
            URL::forceRootUrl(null);
            URL::forceScheme(null);
            // Вложенные запросы подменяют текущий запрос и сессию (StartSession) — возвращаем как было,
            // чтобы публикация из админки посреди запроса не сбила её сессию.
            app()->instance('request', $originalRequest);
            Facade::clearResolvedInstance('request');
            if ($sessionState) {
                [$id, $attributes] = $sessionState;
                $session->setId($id);
                $session->flush();
                $session->put($attributes);
            }
        }
    }

    private function fileFor(string $path): string
    {
        $path = trim($path, '/');
        if ($path === '') {
            return 'index.html';
        }

        return str_contains(basename($path), '.') ? $path : $path.'/index.html';
    }
}
