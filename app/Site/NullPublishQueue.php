<?php

namespace App\Site;

class NullPublishQueue implements PublishQueue
{
    public function request(): void {}
}
