<?php

use App\Content\ContentRepository;

it('renders both directions with all items', function () {
    $c = app(ContentRepository::class);
    $r = $this->get('/')
        ->assertSee('Обучение и спикерство')
        ->assertSee('Организация публичных мероприятий')
        ->assertSee('Ознакомьтесь с форматами обучений')
        ->assertSee('Ознакомьтесь с темами обучений')
        ->assertSee($c->trainingIntro());
    foreach ($c->topicGroups() as $g) {
        $r->assertSee($g['title']);
        foreach ($g['items'] as $i) {
            $r->assertSee($i['title']);
        }
    }
    foreach ($c->eventServices() as $s) {
        $r->assertSee($s['title'])->assertSee($s['text']);
    }
    expect(substr_count($r->getContent(), 'data-format-card'))->toBe(8);
});

it('has KP buttons for both directions', fn () => $this->get('/')
    ->assertSee('data-kp-source="training"', false)
    ->assertSee('data-kp-source="events-org"', false));
