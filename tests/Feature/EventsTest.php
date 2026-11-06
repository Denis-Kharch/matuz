<?php

use App\Content\ContentRepository;

it('home shows 6 latest events and link to all', function () {
    $c = app(ContentRepository::class);
    $r = $this->get('/')
        ->assertSee('Лента последних событий')
        ->assertSee('Открыть всю ленту')
        ->assertSee('href="'.route('events.index').'"', false);
    expect(substr_count($r->getContent(), 'data-event-item'))->toBe(min(6, count($c->events())));
    $r->assertSee($c->events()[0]['title'])->assertDontSee($c->events()[6]['title']);
});

it('events page lists all, newest first, with russian dates', function () {
    $e = app(ContentRepository::class)->events();
    $r = $this->get('/events')->assertOk()
        ->assertSee('<title>Лента событий · Андрей Матузов</title>', false)
        ->assertSeeInOrder([$e[0]['title'], $e[1]['title'], $e[count($e) - 1]['title']])
        ->assertSee('14 марта 2025')
        ->assertDontSee('март 2025<');
    expect(substr_count($r->getContent(), 'data-event-item'))->toBe(count($e));
});

it('event deep link opens it; unknown 404s', function () {
    $e = app(ContentRepository::class)->events()[0];
    $this->get('/events/'.$e['slug'])->assertOk()
        ->assertSee('data-open-overlay="event-'.$e['slug'].'"', false)
        ->assertSee('<title>'.e($e['title']).' · Андрей Матузов</title>', false)
        ->assertSee($e['description']);
    $this->get('/events/nope')->assertNotFound();
});
