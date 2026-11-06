<?php

use App\Content\ContentRepository;

it('renders hero copy, KP button and contacts', function () {
    $this->get('/')
        // Неразрывный пробел перед тире — типографика, текст тот же.
        ->assertSee("Андрей Матузов\u{00A0}— ваш деловой партнёр в организации эффективного обучения")
        ->assertSee('От анализа и разработки до проведения и результата')
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
