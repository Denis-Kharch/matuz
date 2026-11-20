<?php

use App\Support\CaseDate;

it('parses case date labels into sortable dates', function (?string $label, ?string $date) {
    expect(CaseDate::parse($label))->toBe($date);
})->with([
    ['март 2025', '2025-03-01'],
    ['март–апрель 2025', '2025-04-01'],
    ['сентября–октября 2024', '2024-10-01'],
    ['Май 2022', '2022-05-01'],
    ['14.03.2025', '2025-03-14'],
    ['2024', '2024-01-01'],
    ['весна', null],
    [null, null],
]);

it('builds a label from a date', fn () => expect(CaseDate::label('2025-05-20'))->toBe('май 2025'));
