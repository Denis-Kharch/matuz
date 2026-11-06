<?php

use App\Content\ContentRepository;

it('renders KP form with exact heading and no personal fields', function () {
    $html = $this->get('/')
        ->assertSee('Прочитав коммерческое предложение — вы уже на полпути к результатам')
        ->assertSee('Название компании')
        ->assertSee('Город')
        ->assertSee('Сформировать КП')
        ->getContent();
    expect($html)->not->toMatch('/type="(tel|email)"/')
        ->not->toContain('name="phone"')
        ->not->toContain('name="email"');
});

it('renders KP document template with prices, topics and achievements', function () {
    $kp = app(ContentRepository::class)->kp();
    $r = $this->get('/')->assertSee('data-kp-document', false)
        ->assertSee('Коммерческое предложение')
        ->assertSee($kp['terms']);
    foreach ($kp['prices'] as $p) {
        $r->assertSee($p['label'])->assertSee($p['price']);
    }
    foreach ($kp['achievements'] as $a) {
        $r->assertSee($a);
    }
});

it('KP is available on the events page too', fn () => $this->get('/events')
    ->assertSee('data-kp-document', false)
    ->assertSee('Прочитав коммерческое предложение'));

it('has no kp-endpoint meta on stage 1', fn () => $this->get('/')->assertDontSee('name="kp-endpoint"', false));
