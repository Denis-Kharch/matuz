<?php

/*
 * Быстрая выкладка без SSH: CI кладёт приложение одним архивом по FTPS в storage/app/deploy/app.zip,
 * затем вызывает этот файл (POST, заголовок X-Deploy-Token = DEPLOY_TOKEN из .env приложения).
 * Архив распаковывается рядом, и папки приложения подменяются переименованием — за доли секунды.
 * Не трогаются: .env, storage/, база database/*.sqlite*, метка .matuzov-app и всё, чего нет в архиве.
 * CI подставляет __APP_PATH__ (абсолютный путь приложения). Лежит в папке сайта рядом с app.php.
 */

/** Распаковать архив приложения и подменить его папки. Возвращает отчёт; при ошибке — исключение. */
function matuzov_unpack(string $app): array
{
    $zipFile = $app.'/storage/app/deploy/app.zip';
    $new = $app.'/.release-new';
    $old = $app.'/.release-old';
    if (! is_file($zipFile)) {
        throw new RuntimeException('Нет архива storage/app/deploy/app.zip');
    }
    if (! class_exists(ZipArchive::class)) {
        throw new RuntimeException('В PHP нет расширения zip');
    }
    matuzov_rrmdir($new);
    matuzov_rrmdir($old);

    $zip = new ZipArchive;
    if ($zip->open($zipFile) !== true) {
        throw new RuntimeException('Архив не открывается');
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = (string) $zip->getNameIndex($i);
        if ($name === '' || $name[0] === '/' || str_contains($name, '\\') || str_contains('/'.$name.'/', '/../') || str_contains($name, ':')) {
            $zip->close();
            throw new RuntimeException('Недопустимый путь в архиве: '.$name);
        }
    }
    $files = $zip->numFiles;
    $okExtract = $zip->extractTo($new);
    $zip->close();
    if (! $okExtract || ! is_file($new.'/vendor/autoload.php') || ! is_file($new.'/bootstrap/app.php') || ! is_file($new.'/artisan')) {
        matuzov_rrmdir($new);
        throw new RuntimeException('Архив распакован не полностью — приложение не тронуто');
    }

    // Что подменяем: всё верхнего уровня из архива; в database/ — по одному, чтобы база осталась на месте.
    $items = [];
    foreach (matuzov_children($new) as $top) {
        if ($top === 'database' && is_dir($new.'/database')) {
            foreach (matuzov_children($new.'/database') as $child) {
                $items[] = 'database/'.$child;
            }
            // Устаревшее в database/ (кроме базы) тоже убираем.
            foreach (is_dir($app.'/database') ? matuzov_children($app.'/database') : [] as $child) {
                if (! preg_match('/\.sqlite/', $child) && ! file_exists($new.'/database/'.$child)) {
                    $items[] = 'database/'.$child;
                }
            }
            is_dir($app.'/database') || mkdir($app.'/database', 0755, true);
            continue;
        }
        $items[] = $top;
    }
    $items = array_values(array_unique($items));

    $moved = []; // для отката: что уже переложено в .release-old
    $placed = [];
    try {
        foreach ($items as $rel) {
            $cur = $app.'/'.$rel;
            if (file_exists($cur) || is_link($cur)) {
                is_dir(dirname($old.'/'.$rel)) || mkdir(dirname($old.'/'.$rel), 0755, true);
                if (! rename($cur, $old.'/'.$rel)) {
                    throw new RuntimeException('Не удалось убрать '.$rel);
                }
                $moved[] = $rel;
            }
            if (file_exists($new.'/'.$rel)) {
                if (! rename($new.'/'.$rel, $cur)) {
                    throw new RuntimeException('Не удалось поставить '.$rel);
                }
                $placed[] = $rel;
            }
        }
    } catch (Throwable $e) {
        // Откат: новое — обратно в .release-new, старое — на место.
        foreach (array_reverse($placed) as $rel) {
            @rename($app.'/'.$rel, $new.'/'.$rel);
        }
        foreach (array_reverse($moved) as $rel) {
            @rename($old.'/'.$rel, $app.'/'.$rel);
        }
        throw $e;
    }

    // Папки для записи: после подмены bootstrap/ кеша нет — его пересоберёт хук установки.
    foreach (['bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $dir) {
        is_dir($app.'/'.$dir) || mkdir($app.'/'.$dir, 0755, true);
    }
    matuzov_rrmdir($new);
    matuzov_rrmdir($old);
    @unlink($zipFile);
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }

    return ['ok' => true, 'files' => $files, 'replaced' => count($placed)];
}

/** @return list<string> */
function matuzov_children(string $dir): array
{
    return array_values(array_diff(scandir($dir) ?: [], ['.', '..']));
}

function matuzov_rrmdir(string $path): void
{
    if (is_link($path) || is_file($path)) {
        @unlink($path);

        return;
    }
    if (! is_dir($path)) {
        return;
    }
    foreach (matuzov_children($path) as $child) {
        matuzov_rrmdir($path.'/'.$child);
    }
    @rmdir($path);
}

// Веб-запрос (а не подключение файла из тестов).
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $app = '__APP_PATH__';
    $reply = static function (int $code, array $body): never {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($body, JSON_UNESCAPED_UNICODE);
        exit;
    };
    $env = is_file($app.'/.env') ? (string) file_get_contents($app.'/.env') : '';
    $expected = preg_match('/^DEPLOY_TOKEN=["\']?([A-Za-z0-9_-]+)["\']?\s*$/m', $env, $m) ? $m[1] : '';
    $token = (string) ($_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || $expected === '' || ! hash_equals($expected, $token)) {
        http_response_code(404);
        exit;
    }
    // Та же блокировка, что у хука установки: не подменять код посреди установки.
    @mkdir($app.'/storage/framework', 0755, true);
    $lock = @fopen($app.'/storage/framework/deploy.lock', 'c');
    if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
        $reply(423, ['ok' => false, 'error' => 'Идёт установка — повторите позже']);
    }
    @set_time_limit(0);
    ignore_user_abort(true);
    try {
        $result = matuzov_unpack($app);
    } catch (Throwable $e) {
        $reply(500, ['ok' => false, 'error' => $e->getMessage()]);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
    $reply(200, $result);
}
