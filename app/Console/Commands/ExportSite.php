<?php

namespace App\Console\Commands;

use App\Site\SiteBuilder;
use App\Site\SitePublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

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
    private const SKIP = ['index.php', '.htaccess', 'hot', 'web.config', SitePublisher::MANIFEST];

    public function handle(SiteBuilder $builder): int
    {
        $out = $this->option('out');
        $out = str_starts_with($out, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $out) ? $out : base_path($out);

        try {
            $files = $builder->render();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        File::deleteDirectory($out);
        File::ensureDirectoryExists($out);
        // Сначала public/, затем свежие страницы поверх (в public/ могут лежать прошлые публикации).
        File::copyDirectory(public_path(), $out);
        foreach (self::SKIP as $skip) {
            File::delete("{$out}/{$skip}");
        }

        foreach ($files as $file => $contents) {
            File::ensureDirectoryExists(dirname("{$out}/{$file}"));
            File::put("{$out}/{$file}", $contents);
            $this->line("  {$file}");
        }

        File::put("{$out}/.nojekyll", '');

        $this->info('Готово: '.$out);

        return self::SUCCESS;
    }
}
