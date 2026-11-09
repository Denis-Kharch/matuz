<?php

namespace App\Console\Commands;

use App\Site\SitePublisher;
use Illuminate\Console\Command;

class PublishSite extends Command
{
    protected $signature = 'site:publish';

    protected $description = 'Пересобрать публичный сайт в папку сайта (public_path)';

    public function handle(SitePublisher $publisher): int
    {
        $result = $publisher->publishNow();
        if ($result === null) {
            $this->warn('Публикация уже идёт — после неё будет выполнена ещё одна.');

            return self::SUCCESS;
        }
        if (! $result->ok) {
            $this->error('Ошибка публикации: '.$result->error);

            return self::FAILURE;
        }
        $this->info("Опубликовано файлов: {$result->pages} → ".public_path());

        return self::SUCCESS;
    }
}
