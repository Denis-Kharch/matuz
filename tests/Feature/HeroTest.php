<?php

use App\Content\ContentRepository;

it('renders hero copy, KP button and contacts', function () {
    $r = $this->get('/');
    // Заголовок разбит на слова для анимации при старте — текст заголовка без тегов тот же;
    // неразрывный пробел перед тире — типографика.
    preg_match('#<h1[^>]*>(.*?)</h1>#s', $r->getContent(), $h1);
    expect(html_entity_decode(strip_tags($h1[1])))->toBe("Андрей Матузов\u{00A0}— ваш деловой партнёр в организации эффективного обучения");
    $r->assertSee('От анализа и разработки до проведения и результата')
        ->assertSee('Мы не собираем персональные данные — только название компании и город.')
        ->assertSee('data-kp-source="hero"', false)
        ->assertSee('tel:+79186428895', false)
        ->assertSee('https://t.me/a_matuzov', false);
});

it('puts copy opposite the subject', fn () => $this->get('/')->assertSee('data-copy-side="right"', false));

it('hero image is eager with focal point', fn () => $this->get('/')
    ->assertSee('data-hero-img', false)
    ->assertSee('fetchpriority="high"', false)
    ->assertSee('object-position: 24% 30%', false));

it('renders all principles in the marquee', function () {
    $r = $this->get('/');
    foreach (app(ContentRepository::class)->principles() as $p) {
        $r->assertSee($p);
    }
});
