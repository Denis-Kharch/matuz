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

it('renders partners as grid, marquee or marquee on mobile only', function (array $display, string $mode, bool $grid, bool $marquee) {
    app()->instance(ContentRepository::class, new class($display) extends App\Content\StaticContentRepository
    {
        public function __construct(private array $display) {}

        public function partnersDisplay(): array
        {
            return $this->display;
        }
    });
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('data-partners-mode="'.$mode.'"')
        ->and(str_contains($html, 'data-partners-grid'))->toBe($grid)
        ->and(str_contains($html, 'data-partners-marquee'))->toBe($marquee);
})->with([
    'grid' => [['marquee' => false, 'mobile_only' => false], 'grid', true, false],
    'marquee' => [['marquee' => true, 'mobile_only' => false], 'marquee', false, true],
    'marquee on mobile' => [['marquee' => true, 'mobile_only' => true], 'marquee-mobile', true, true],
]);

it('gallery is a single scrollable strip', function () {
    $html = $this->get('/')->getContent();
    expect($html)->toContain('data-gallery-strip')->toContain('Листать галерею: вперёд');
});

it('has KP buttons in simple blocks', function () {
    $r = $this->get('/');
    foreach (['partners', 'faq', 'certificates', 'contacts'] as $src) {
        $r->assertSee('data-kp-source="'.$src.'"', false);
    }
});
