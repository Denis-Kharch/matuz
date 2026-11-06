<?php

use App\Support\Ru;

it('picks russian plural forms', function (int $n, string $expected) {
    expect(Ru::plural($n, ['тема', 'темы', 'тем']))->toBe($expected);
})->with([
    [1, 'тема'], [2, 'темы'], [4, 'темы'], [5, 'тем'], [6, 'тем'], [8, 'тем'],
    [11, 'тем'], [12, 'тем'], [14, 'тем'], [21, 'тема'], [22, 'темы'], [25, 'тем'], [0, 'тем'],
]);
