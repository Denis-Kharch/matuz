<?php

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(RefreshDatabase::class);

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

function quietMedia(array $attrs): Media
{
    $m = (new Media)->forceFill(['collection' => 'x', 'original_path' => 'media-originals/x.jpg', ...$attrs]);
    $m->saveQuietly();

    return $m;
}

it('renders picture for a db media key from its own base and formats', function () {
    quietMedia(['key' => 'media/7-ab12cd34', 'widths' => [640, 1280], 'formats' => ['webp'], 'width' => 1600, 'height' => 1000, 'focal_x' => 30, 'focal_y' => 20]);
    $html = Blade::render('<x-picture src="media/7-ab12cd34" alt="Фото" />');
    expect($html)->toContain('media/7-ab12cd34-640.webp 640w')
        ->toContain('media/7-ab12cd34-1280.webp')
        ->toContain('width="1600"')
        ->toContain('object-position: 30% 20%')
        ->not->toContain('type="image/avif"')
        ->not->toContain('img/media/');
});

it('explicit focal wins over the media focal', function () {
    quietMedia(['key' => 'media/8-ab12cd34', 'widths' => [640], 'formats' => ['avif', 'webp'], 'width' => 640, 'height' => 400, 'focal_x' => 30, 'focal_y' => 20]);
    $html = Blade::render('<x-picture src="media/8-ab12cd34" :focal="[\'x\' => 10, \'y\' => 90]" />');
    expect($html)->toContain('type="image/avif"')->toContain('object-position: 10% 90%');
});
