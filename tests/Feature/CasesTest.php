<?php

use App\Content\ContentRepository;
use Illuminate\Support\Str;

it('shows case strip with 8 cards linking to case pages and feed button', function () {
    $c = app(ContentRepository::class);
    $r = $this->get('/')
        ->assertSee('Реальная практика, подтверждённая результатами')
        ->assertSee('Посмотреть актуальные кейсы в ленте событий Андрея')
        ->assertSee('href="'.route('events.index').'"', false);
    expect(substr_count($r->getContent(), 'data-case-card'))->toBe(8);
    $r->assertSee('data-cases-strip', false)->assertSee('Листать кейсы: вперёд');
    foreach ($c->cases() as $case) {
        $r->assertSee('href="'.route('cases.show', $case['slug']).'"', false);
    }
});

it('case link renders a standalone case page without a copy of the home page', function () {
    $case = app(ContentRepository::class)->cases()[0];
    $r = $this->get('/cases/'.$case['slug'])
        ->assertOk()
        ->assertSee('data-case-page="'.$case['slug'].'"', false)
        ->assertSee('<title>'.e($case['title']).' — кейс · Андрей Матузов</title>', false)
        ->assertSee('<h1 class="mt-5', false)
        ->assertSee('Все кейсы')
        // Главная не дублируется: нет её разделов и нет окна поверх.
        ->assertDontSee('id="faq"', false)->assertDontSee('id="gallery"', false)->assertDontSee('data-open-overlay=', false)
        ->assertSee('<link rel="canonical" href="'.route('cases.show', $case['slug']).'">', false)
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee($case['request'])
        ->assertSee($case['result'])
        ->assertSee($case['review']);
    // План выводится по шагам (разделитель «→» в данных).
    foreach (preg_split('/\s*→\s*/u', $case['plan']) as $step) {
        $r->assertSee(Str::ucfirst($step));
    }
});

it('home has no overlay open by default', fn () => $this->get('/')->assertDontSee('data-open-overlay=', false));

it('404s on unknown case', fn () => $this->get('/cases/nope')->assertNotFound());

it('case cards have a morph thumbnail and overlays have a banner', function () {
    $html = $this->get('/')->getContent();
    expect(substr_count($html, 'data-case-thumb'))->toBe(8)
        ->and(substr_count($html, 'data-case-banner'))->toBe(8);
});
