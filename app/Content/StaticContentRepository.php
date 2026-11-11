<?php

namespace App\Content;

class StaticContentRepository implements ContentRepository
{
    /** @var array<string, mixed> */
    private array $cache = [];

    private function load(string $name): mixed
    {
        return $this->cache[$name] ??= require resource_path("content/{$name}.php");
    }

    public function hero(): array
    {
        return $this->load('hero');
    }

    public function principles(): array
    {
        return $this->load('principles');
    }

    public function about(): array
    {
        return $this->load('about');
    }

    public function experience(): array
    {
        return $this->load('experience');
    }

    public function trainingIntro(): string
    {
        return $this->load('training-intro');
    }

    public function formats(): array
    {
        return $this->load('formats');
    }

    public function topicGroups(): array
    {
        return $this->load('topics');
    }

    public function eventServices(): array
    {
        return $this->load('event-services');
    }

    public function cases(): array
    {
        return array_values(array_map(fn (array $c) => $c + ['plan_steps' => self::planSteps($c['plan']), 'cover' => $c['photos'][0]], $this->load('cases')));
    }

    /** Шаги плана кейса: делим по «→», первая буква заглавная. */
    public static function planSteps(string $plan): array
    {
        return collect(explode('→', $plan))
            ->map(fn ($step) => trim($step))
            ->filter()
            ->map(fn ($step) => mb_strtoupper(mb_substr($step, 0, 1)).mb_substr($step, 1))
            ->values()
            ->all();
    }

    public function findCase(string $slug): ?array
    {
        return collect($this->cases())->firstWhere('slug', $slug);
    }

    public function events(?int $limit = null): array
    {
        $events = collect($this->load('events'))->sortByDesc('date')->values();

        return ($limit === null ? $events : $events->take($limit))->all();
    }

    public function findEvent(string $slug): ?array
    {
        return collect($this->load('events'))->firstWhere('slug', $slug);
    }

    public function partners(): array
    {
        return $this->load('partners');
    }

    public function partnersDisplay(): array
    {
        return ['marquee' => false, 'mobile_only' => false];
    }

    public function faq(): array
    {
        return $this->load('faq');
    }

    public function gallery(): array
    {
        return $this->load('gallery');
    }

    public function certificates(): array
    {
        return $this->load('certificates');
    }

    public function contacts(): array
    {
        return $this->load('contacts');
    }

    public function kp(): array
    {
        return $this->load('kp');
    }

    public function seo(): array
    {
        return [
            'yandex_verification' => (string) config('site.seo.yandex_verification', ''),
            'google_verification' => (string) config('site.seo.google_verification', ''),
            'metrika_id' => (string) config('site.seo.metrika_id', ''),
        ];
    }

    public function lastModified(): ?string
    {
        return null;
    }
}
