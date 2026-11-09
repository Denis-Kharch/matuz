<?php

namespace App\Models\Concerns;

use App\Site\PublishQueue;

trait TriggersPublish
{
    public static function bootTriggersPublish(): void
    {
        $request = fn () => app(PublishQueue::class)->request();
        static::saved($request);
        static::deleted($request);
    }
}
