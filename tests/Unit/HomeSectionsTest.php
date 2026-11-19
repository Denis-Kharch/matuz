<?php

use App\Content\HomeSections;

it('keeps saved order, drops unknown keys and duplicates, appends new blocks visible', function () {
    $out = HomeSections::normalize([
        ['key' => 'faq', 'visible' => false],
        ['key' => 'hero'],            // первый экран не двигается — в списке его нет
        ['key' => 'nope'],
        ['key' => 'faq', 'visible' => true],
        'garbage',
    ]);
    expect($out[0])->toBe(['key' => 'faq', 'visible' => false])
        ->and(array_column($out, 'key'))->toHaveCount(count(HomeSections::LABELS))
        ->and(array_column($out, 'key'))->not->toContain('hero', 'nope')
        ->and(collect($out)->where('key', '!=', 'faq')->every(fn ($s) => $s['visible']))->toBeTrue();
    expect(HomeSections::normalize(null))->toBe(HomeSections::defaults());
});
