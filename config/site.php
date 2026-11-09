<?php

return [
    // Превью-режим (GitHub Pages): переключатель шрифтов A/B, robots Disallow.
    'preview' => (bool) env('SITE_PREVIEW', false),

    // Папка публичного сайта на хостинге (статика + app.php). Пусто — public/ приложения.
    'public_path' => env('PUBLIC_SITE_PATH') ?: public_path(),

    // Источник контента: 'static' (resources/content/*.php) или 'database' (админка).
    'content_source' => env('CONTENT_SOURCE', 'static'),

    // Токен запуска пересборки сайта из CI.
    'deploy_token' => env('DEPLOY_TOKEN'),

    // Адрес приёма заявок на КП (null — мета kp-endpoint не выводится).
    'kp_endpoint' => env('KP_ENDPOINT'),
];
