<?php

it('shows the brand logo in the header, KP document and favicons', function () {
    $html = $this->get('/')->getContent();
    expect($html)->toMatch('/data-header-bar.*?<img[^>]+src="[^"]*brand\/logo-96\.webp"[^>]*alt="MATUZOV\.RU"/s')
        ->toMatch('/data-kp-document.*?<img[^>]+brand\/logo-192\.png/s')
        ->toContain('rel="icon" href="'.asset('brand/favicon-32.png').'"')
        ->toContain('rel="apple-touch-icon" href="'.asset('brand/apple-touch-icon.png').'"');
});

it('ships logo files', function () {
    foreach (['logo-96.webp', 'logo-192.png', 'favicon-32.png', 'apple-touch-icon.png'] as $f) {
        expect(is_file(public_path('brand/'.$f)))->toBeTrue("missing public/brand/{$f}");
    }
});
