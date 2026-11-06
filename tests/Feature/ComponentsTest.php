<?php

use Illuminate\Support\Facades\Blade;

it('renders picture with avif+webp srcset, size attrs and lazy loading', function () {
    $html = Blade::render('<x-picture src="drafts/stage" alt="Сцена" />');
    expect($html)->toContain('type="image/avif"')
        ->toContain('drafts/stage-1280.webp')
        ->toContain('loading="lazy"')
        ->toContain('alt="Сцена"')
        ->toMatch('/width="\d+"\s+height="\d+"/')
        ->not->toContain('src="/img')
        ->not->toContain('srcset="/img');
});

it('renders eager picture with focal point', function () {
    $html = Blade::render('<x-picture src="drafts/stage" alt="" :eager="true" :focal="[\'x\' => 24, \'y\' => 30]" />');
    expect($html)->toContain('fetchpriority="high"')
        ->toContain('object-position: 24% 30%')
        ->not->toContain('loading="lazy"');
});

it('renders kp button with source and dispatch', function () {
    $html = Blade::render('<x-kp-button source="cases">Получить моментальное КП</x-kp-button>');
    expect($html)->toContain('data-kp-source="cases"')
        ->toContain("\$dispatch('kp-open', { source: 'cases' })")
        ->toContain('Получить моментальное КП');
});

it('renders button variants', function () {
    expect(Blade::render('<x-button href="#x">Да</x-button>'))->toContain('<a')->toContain('btn-primary');
    expect(Blade::render('<x-button variant="quiet" type="submit">Да</x-button>'))->toContain('<button')->toContain('btn-quiet')->toContain('type="submit"');
});

it('renders section with id and heading', function () {
    $html = Blade::render('<x-section id="faq" title="Частые вопросы" kicker="FAQ">тело</x-section>');
    expect($html)->toContain('id="faq"')->toContain('<h2')->toContain('Частые вопросы')->toContain('тело');
});
