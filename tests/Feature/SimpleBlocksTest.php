<?php

use App\Content\ContentRepository;

it('renders partners, faq, gallery, certificates, contacts', function () {
    $c = app(ContentRepository::class);
    $r = $this->get('/')
        ->assertSee('Друзья и партнёры')
        ->assertSee('Частые вопросы к Андрею')
        ->assertSee('Галерея')
        ->assertSee('Регалии и сертификаты')
        ->assertSee('Всё уже в ваших руках, остался последний шаг')
        ->assertSee($c->contacts()['vk'], false)
        ->assertSee($c->contacts()['telegram'], false)
        ->assertSee($c->contacts()['max'], false)
        ->assertSee($c->contacts()['phone']);
    foreach ($c->partners() as $p) {
        $r->assertSee($p['name'])->assertSee($p['description']);
    }
    foreach ($c->faq() as $f) {
        $r->assertSee($f['q'])->assertSee($f['a']);
    }
    foreach ($c->certificates() as $cert) {
        $r->assertSee($cert['title'])->assertSee($cert['year']);
    }
    expect(substr_count($r->getContent(), 'data-gallery-item'))->toBe(count($c->gallery()));
});

it('has KP buttons in simple blocks', function () {
    $r = $this->get('/');
    foreach (['partners', 'faq', 'certificates', 'contacts'] as $src) {
        $r->assertSee('data-kp-source="'.$src.'"', false);
    }
});
