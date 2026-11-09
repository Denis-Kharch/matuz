<?php

use App\Support\RichText;

it('keeps allowed tags and strips dangerous ones', function () {
    $out = (string) RichText::render('<p onclick="x">Привет <b>мир</b> <script>alert(1)</script><font color=red>!</font> <a href="javascript:x">j</a> <a href="https://a.ru">a</a></p>');
    expect($out)->toContain('<b>мир</b>')->toContain('!')->not->toContain('script')->not->toContain('onclick')
        ->not->toContain('font')->not->toContain('javascript:')->toContain('href="https://a.ru"')->toContain('rel="noopener"');
});

it('escapes plain text and keeps line breaks', fn () => expect((string) RichText::render("a < b\nc"))->toBe("a &lt; b<br />\nc"));

it('pastes from word without losing text', fn () => expect(strip_tags((string) RichText::render('<p class="MsoNormal" style="mso-x:1"><span style="font-size:14pt">Текст</span></p>')))->toBe('Текст'));

it('renders null as empty', fn () => expect((string) RichText::render(null))->toBe(''));

it('makes a one-line plain preview', fn () => expect(RichText::plain('<p>a &lt; b</p><p>c <a href="https://a.ru">d</a></p>'))->toBe('a < b c d'));

it('keeps text of unknown tags from word and editors', function () {
    $out = (string) RichText::render('<p><st1:City>Москва</st1:City>, <abbr title="x">HR</abbr>, <cite>Источник</cite>, <figure><figcaption>Подпись</figcaption></figure></p><script>alert(1)</script><style>p{}</style>');
    expect($out)->toContain('Москва')->toContain('HR')->toContain('Источник')->toContain('Подпись')
        ->not->toContain('script')->not->toContain('style')->not->toContain('alert')->not->toContain('p{}');
});
