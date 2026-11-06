<?php

use App\Content\ContentRepository;

beforeEach(fn () => $this->c = app(ContentRepository::class));

it('has hero copy from the brief', function () {
    expect($this->c->hero()['title'])->toBe('Андрей Матузов — ваш деловой партнёр в организации эффективного обучения')
        ->and($this->c->hero()['subtitle'])->toBe('От анализа и разработки до проведения и результата')
        ->and($this->c->hero()['subject_side'])->toBeIn(['left', 'right']);
});

it('has 8 principles numbered consecutively', fn () => expect($this->c->principles())->toHaveCount(8)
    ->and($this->c->principles()[0])->toStartWith('Принцип №1')
    ->and($this->c->principles()[7])->toStartWith('Принцип №8'));

it('has 4 experience stages', fn () => expect($this->c->experience())->toHaveCount(4));

it('has 8 formats', fn () => expect($this->c->formats())->toHaveCount(8));

it('has 5 topic groups with 4–8 items', function () {
    expect($this->c->topicGroups())->toHaveCount(5);
    foreach ($this->c->topicGroups() as $g) {
        expect(count($g['items']))->toBeGreaterThanOrEqual(4)->toBeLessThanOrEqual(8);
    }
});

it('has 7 event services', fn () => expect($this->c->eventServices())->toHaveCount(7));

it('has 8 cases, both directions, exactly 3 photos each, unique slugs', function () {
    $cases = $this->c->cases();
    expect($cases)->toHaveCount(8)
        ->and(collect($cases)->pluck('direction')->unique()->sort()->values()->all())->toBe(['events', 'training'])
        ->and(collect($cases)->pluck('slug')->unique())->toHaveCount(8);
    foreach ($cases as $case) {
        expect($case['photos'])->toHaveCount(3);
    }
});

it('sorts events newest first and limits', function () {
    $e = $this->c->events();
    expect($e[0]['date'] >= $e[1]['date'])->toBeTrue()
        ->and($this->c->events(6))->toHaveCount(min(6, count($e)));
    foreach ($e as $ev) {
        expect(count($ev['photos']))->toBeGreaterThanOrEqual(3)->toBeLessThanOrEqual(6);
    }
});

it('finds by slug or returns null', function () {
    expect($this->c->findCase($this->c->cases()[0]['slug']))->not->toBeNull()
        ->and($this->c->findCase('nope'))->toBeNull()
        ->and($this->c->findEvent($this->c->events()[0]['slug']))->not->toBeNull()
        ->and($this->c->findEvent('nope'))->toBeNull();
});

it('has contacts', fn () => expect($this->c->contacts())->toMatchArray([
    'phone' => '+7 918 642 88 95', 'phone_href' => 'tel:+79186428895',
    'vk' => 'https://vk.ru/matuzovtrener', 'telegram' => 'https://t.me/a_matuzov',
]));
