<?php

namespace App\Site;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

/**
 * Пересборка публичного сайта прямо в его папку (public_path()).
 * Страницы рендерятся целиком заранее: если рендер или проверка упали, файлы не меняются.
 * Затем каждый файл пишется во временный рядом и переименовывается поверх старого.
 * Удаляются только страницы из прошлого списка .matuzov-pages.json, которых больше нет.
 */
class SitePublisher implements PublishQueue
{
    public const LOCK = 'site-publish';

    public const AGAIN = 'site-publish:again';

    public const MANIFEST = '.matuzov-pages.json';

    /** Файлы и папки сайта, которые публикация не пишет и не удаляет никогда. */
    private const PROTECTED_DIRS = ['media', 'build', 'brand', 'img'];

    private const PROTECTED_FILES = ['.htaccess', 'app.php', 'index.php', 'favicon.ico', 'site.webmanifest', self::MANIFEST];

    /** Обязательные файлы готового сайта. */
    private const REQUIRED = ['index.html', '404.html', 'sitemap.xml'];

    /** Публикация уже запланирована на конец текущего запроса. */
    private bool $pending = false;

    public function __construct(private SiteBuilder $builder) {}

    /**
     * Запрос из сохранения модели: одна публикация на запрос, после отправки ответа
     * (и после записи файлов фото). В консоли — по завершении команды, в тестах — по app()->terminate().
     * Не dispatch(fn)->afterResponse(): при QUEUE_CONNECTION=sync замыкание сериализуется вместе с $this.
     */
    public function request(): void
    {
        if ($this->pending) {
            return;
        }
        $this->pending = true;
        app()->terminating(function () {
            if (! $this->pending) {
                return; // колбэк уже отработал (terminate вызван повторно)
            }
            $this->pending = false;
            $this->publishNow();
        });
    }

    /**
     * Опубликовать под блокировкой, повторяя, пока во время работы приходили новые запросы.
     * Если блокировка занята — поднять флаг для её держателя и вернуть null.
     */
    public function publishNow(): ?PublishResult
    {
        $lock = Cache::lock(self::LOCK, 120);
        if (! $lock->get()) {
            Cache::put(self::AGAIN, true, now()->addHour());

            return null;
        }
        try {
            do {
                Cache::forget(self::AGAIN);
                $result = $this->publish();
            } while (Cache::get(self::AGAIN));

            return $result;
        } finally {
            $lock->release();
        }
    }

    public function publish(): PublishResult
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        ignore_user_abort(true);

        try {
            $files = $this->builder->render();
            $this->check($files);
            $this->write($files);
            $result = new PublishResult(true, CarbonImmutable::now(), null, count($files));
        } catch (Throwable $e) {
            report($e);
            $result = new PublishResult(false, CarbonImmutable::now(), $e->getMessage());
        }
        Setting::putQuietly('publish_status', $result->toArray());

        return $result;
    }

    /** @param array<string, string> $files */
    private function check(array $files): void
    {
        foreach (self::REQUIRED as $file) {
            if (! isset($files[$file])) {
                throw new RuntimeException("Нет обязательного файла {$file}");
            }
        }
        foreach ($files as $file => $contents) {
            if (! $this->isPublishable($file)) {
                throw new RuntimeException("Недопустимый путь страницы: {$file}");
            }
            if (preg_match('/127\.0\.0\.1|localhost/i', $contents, $m)) {
                throw new RuntimeException("{$file}: ссылка на локальный адрес ({$m[0]}) — проверьте APP_URL");
            }
        }
    }

    /** @param array<string, string> $files */
    private function write(array $files): void
    {
        $previous = $this->previousPages();
        $files[self::MANIFEST] = json_encode(array_keys($files), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // Сначала все временные файлы: если запись упадёт, сайт не тронут.
        $temps = [];
        try {
            foreach ($files as $file => $contents) {
                $target = public_path($file);
                $dir = dirname($target);
                if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
                    throw new RuntimeException("Не удалось создать папку {$dir}");
                }
                $tmp = $target.'.tmp-'.bin2hex(random_bytes(4));
                if (file_put_contents($tmp, $contents) === false) {
                    throw new RuntimeException("Не удалось записать {$tmp}");
                }
                $temps[$tmp] = $target;
            }
            foreach ($temps as $tmp => $target) {
                if (! rename($tmp, $target)) {
                    throw new RuntimeException("Не удалось заменить {$target}");
                }
                unset($temps[$tmp]);
            }
        } finally {
            foreach (array_keys($temps) as $tmp) {
                @unlink($tmp);
            }
        }

        foreach (array_diff($previous, array_keys($files)) as $stale) {
            if ($this->isPublishable($stale)) {
                @unlink(public_path($stale));
                $this->removeEmptyDirs(dirname($stale));
            }
        }
    }

    /** @return list<string> */
    private function previousPages(): array
    {
        $list = is_file($file = public_path(self::MANIFEST)) ? json_decode((string) file_get_contents($file), true) : [];

        return is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
    }

    /** Путь внутри папки сайта, не служебный и не из защищённых папок. */
    private function isPublishable(string $file): bool
    {
        $parts = explode('/', str_replace('\\', '/', $file));

        return $file !== ''
            && ! str_starts_with($file, '/')
            && ! preg_match('/^[A-Za-z]:/', $file)
            && ! array_intersect($parts, ['', '.', '..'])
            && ! in_array($parts[0], self::PROTECTED_DIRS, true)
            && ! in_array($file, self::PROTECTED_FILES, true);
    }

    private function removeEmptyDirs(string $dir): void
    {
        while ($dir !== '.' && $dir !== '' && is_dir($path = public_path($dir)) && count(scandir($path)) === 2) {
            @rmdir($path);
            $dir = dirname($dir);
        }
    }
}
