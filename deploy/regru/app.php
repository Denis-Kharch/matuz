<?php

// Входной файл админки/служебных путей в папке сайта. CI подставляет __APP_PATH__ (абсолютный путь к приложению).
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appPath = '__APP_PATH__';

if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath.'/vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
