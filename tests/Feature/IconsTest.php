<?php

it('links all site icons for search engines and devices', function () {
    $html = $this->get('/')->getContent();
    expect($html)
        ->toContain('<link rel="icon" href="'.asset('favicon.ico').'" sizes="any">')
        ->toContain('<link rel="icon" href="'.asset('brand/favicon-32.png').'" type="image/png" sizes="32x32">')
        ->toContain('<link rel="icon" href="'.asset('brand/favicon-48.png').'" type="image/png" sizes="48x48">')
        ->toContain('<link rel="icon" href="'.asset('brand/favicon-96.png').'" type="image/png" sizes="96x96">')
        ->toContain('<link rel="icon" href="'.asset('brand/favicon-120.png').'" type="image/png" sizes="120x120">')
        ->toContain('<link rel="apple-touch-icon" href="'.asset('brand/apple-touch-icon.png').'" sizes="180x180">')
        ->toContain('<link rel="manifest" href="'.asset('site.webmanifest').'">');
});

it('ships icon files with correct sizes', function () {
    foreach ([16 => null, 32 => 'brand/favicon-32.png', 48 => 'brand/favicon-48.png', 96 => 'brand/favicon-96.png',
        120 => 'brand/favicon-120.png', 180 => 'brand/apple-touch-icon.png', 192 => 'brand/icon-192.png', 512 => 'brand/icon-512.png'] as $size => $file) {
        if ($file === null) {
            continue;
        }
        $info = getimagesize(public_path($file));
        expect($info)->not->toBeFalse()->and([$info[0], $info[1]])->toBe([$size, $size]);
    }
});

it('ships a real multi-size favicon.ico at the site root', function () {
    $ico = file_get_contents(public_path('favicon.ico'));
    // ICONDIR: reserved 0, type 1 (icon), count >= 3 (16, 32, 48)
    $hdr = unpack('vreserved/vtype/vcount', substr($ico, 0, 6));
    expect($hdr['reserved'])->toBe(0)->and($hdr['type'])->toBe(1)->and($hdr['count'])->toBeGreaterThanOrEqual(3);
});

it('ships a web manifest pointing to existing icons', function () {
    $m = json_decode(file_get_contents(public_path('site.webmanifest')), true);
    expect($m['name'])->toBe('Андрей Матузов')->and($m['icons'])->toHaveCount(2);
    foreach ($m['icons'] as $icon) {
        expect(is_file(public_path($icon['src'])))->toBeTrue();
    }
});
