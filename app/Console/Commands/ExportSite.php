<?php

namespace App\Console\Commands;

use App\Content\ContentRepository;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

/**
 * Статичная выгрузка сайта (для GitHub Pages): каждая страница рендерится через HTTP-ядро
 * и пишется в <out>/<path>/index.html; папка public копируется без серверных файлов.
 * Ссылки строятся от APP_URL (может содержать подпапку, например https://user.github.io/repo).
 */
class ExportSite extends Command
{
    protected $signature = 'site:export {--out=dist : Папка выгрузки}';

    protected $description = 'Выгрузить сайт в статичный HTML';

    /** Файлы public/, которые не нужны статичному сайту. */
    private const SKIP = ['index.php', '.htaccess', 'hot', 'web.config'];

    public function handle(ContentRepository $content, Kernel $kernel): int
    {
        $out = $this->option('out');
        $out = str_starts_with($out, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $out) ? $out : base_path($out);

        $root = rtrim(config('app.url'), '/');
        URL::forceRootUrl($root);
        if (str_starts_with($root, 'https://')) {
            URL::forceScheme('https');
        }

        File::deleteDirectory($out);
        File::ensureDirectoryExists($out);

        foreach ($this->paths($content) as $path) {
            $response = $kernel->handle(Request::create($path, 'GET'));
            if ($response->getStatusCode() !== 200) {
                $this->error("{$path}: HTTP {$response->getStatusCode()}");

                return self::FAILURE;
            }
            $file = $out.'/'.$this->fileFor($path);
            File::ensureDirectoryExists(dirname($file));
            File::put($file, $response->getContent());
            $this->line("  {$path} → ".$this->fileFor($path));
        }

        File::copyDirectory(public_path(), $out);
        foreach (self::SKIP as $skip) {
            File::delete("{$out}/{$skip}");
        }
        File::put("{$out}/.nojekyll", '');

        $this->info('Готово: '.$out);

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function paths(ContentRepository $content): array
    {
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

    private function fileFor(string $path): string
    {
        $path = trim($path, '/');
        if ($path === '') {
            return 'index.html';
        }

        return str_contains(basename($path), '.') ? $path : $path.'/index.html';
    }
}
