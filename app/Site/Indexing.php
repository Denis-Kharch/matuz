<?php

namespace App\Site;

use App\Models\Setting;

/** Закрыт ли сайт от поисковиков: из админки (база) либо из SITE_PREVIEW (статичный источник). */
class Indexing
{
    public static function closed(): bool
    {
        if (config('site.content_source') === 'database') {
            $site = Setting::get('site', []);

            return (bool) ($site['noindex'] ?? true);
        }

        return (bool) config('site.preview');
    }
}
