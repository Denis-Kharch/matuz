<?php

it('renders about and 4 stages with images on the left', function () {
    $html = $this->get('/')
        ->assertSee('Матузов Андрей')
        ->assertSee('Российский бизнес-тренер, корпоративный консультант по обучению и развитию персонала, педагог, психолог.')
        ->assertSee('Санкт-Петербург, Россия')
        ->assertSee('Опыт Андрея')
        ->assertSee('Работа с персоналом')
        ->assertSee('Первое касание с HR')
        ->assertSee('Новые горизонты в T&amp;D', false)
        ->assertSee('Путь продолжается')
        ->assertSee('2014–2018')
        ->assertSee('data-kp-source="about"', false)
        ->getContent();
    expect(substr_count($html, 'data-stage-image="left"'))->toBe(4);
});
