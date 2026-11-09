<?php

namespace App\Site;

interface PublishQueue
{
    /** Запросить пересборку и выгрузку публичного сайта. */
    public function request(): void;
}
