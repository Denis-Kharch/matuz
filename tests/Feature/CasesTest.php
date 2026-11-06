<?php

use App\Content\ContentRepository;
use Illuminate\Support\Str;

it('shows case grid with 8 cards linking to case pages and feed button', function () {
    $c = app(ContentRepository::class);
    $r = $this->get('/')
        ->assertSee('Реальная практика, подтверждённая результатами')
        ->assertSee('Посмотреть актуальные кейсы в ленте событий Андрея')
        ->assertSee('href="'.route('events.index').'"', false);
    expect(substr_count($r->getContent(), 'data-case-card'))->toBe(8);
    foreach ($c->cases() as $case) {
        $r->assertSee('href="'.route('cases.show', $case['slug']).'"', false);
    }
});

it('deep link renders home with that case open and its title', function () {
    $case = app(ContentRepository::class)->cases()[0];
    $r = $this->get('/cases/'.$case['slug'])
        ->assertOk()
        ->assertSee('data-open-overlay="case-'.$case['slug'].'"', false)
        ->assertSee('<title>'.e($case['title']).' — кейс · Андрей Матузов</title>', false)
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
