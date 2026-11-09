<?php

namespace App\Site;

use Carbon\CarbonImmutable;

final readonly class PublishResult
{
    public function __construct(
        public bool $ok,
        public CarbonImmutable $at,
        public ?string $error = null,
        public int $pages = 0,
    ) {}

    /** @return array{ok: bool, at: string, error: ?string, pages: int} */
    public function toArray(): array
    {
        return ['ok' => $this->ok, 'at' => $this->at->toIso8601String(), 'error' => $this->error, 'pages' => $this->pages];
    }
}
