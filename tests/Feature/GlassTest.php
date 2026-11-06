<?php

it('uses liquid glass on header, floating KP, hero contacts and KP form', function () {
    $html = $this->get('/')->getContent();
    expect($html)->toMatch('/data-header-bar[^>]*class="[^"]*\bglass\b/')
        ->toMatch('/data-kp-source="floating"[^>]*class="[^"]*\bglass-accent\b|class="[^"]*\bglass-accent\b[^"]*"[^>]*data-kp-source="floating"/')
        ->toMatch('/aria-label="Telegram"[^>]*class="[^"]*\bglass\b/')
        ->toMatch('/data-modal="kp-form".*?class="[^"]*\bglass\b/s');
});

it('ships the refraction svg filter once', function () {
    $html = $this->get('/')->getContent();
    expect(substr_count($html, 'id="liquid-glass"'))->toBe(1);
});
