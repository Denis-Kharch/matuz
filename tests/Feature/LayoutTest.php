<?php

it('has nav links to every section', function () {
    $html = $this->get('/')->getContent();
    foreach (['about', 'directions', 'cases', 'events', 'partners', 'faq', 'gallery', 'certificates', 'contacts'] as $id) {
        expect($html)->toContain('href="#'.$id.'"');
    }
});

it('has the floating KP button', fn () => $this->get('/')->assertSee('data-kp-source="floating"', false));

it('has base SEO and Open Graph', function () {
    $this->get('/')
        ->assertSee('<html lang="ru"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('name="description"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('<title>Андрей Матузов — бизнес-тренер, T&amp;D-партнёр, спикер · Санкт-Петербург</title>', false);
});

it('shows font switcher only in preview mode', function () {
    config(['site.preview' => true]);
    $this->get('/')->assertSee('data-font-switch', false);
    config(['site.preview' => false]);
    $this->get('/')->assertDontSee('data-font-switch', false);
});
