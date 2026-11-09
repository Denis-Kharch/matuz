<?php

namespace App\Console\Commands;

use App\Content\ContentImporter;
use Illuminate\Console\Command;

class ImportContent extends Command
{
    protected $signature = 'content:import';

    protected $description = 'Перенести статичное наполнение в базу (если кейсов ещё нет)';

    public function handle(ContentImporter $importer): int
    {
        $this->info($importer->importIfEmpty() ? 'Наполнение перенесено.' : 'База уже заполнена — ничего не сделано.');

        return self::SUCCESS;
    }
}
