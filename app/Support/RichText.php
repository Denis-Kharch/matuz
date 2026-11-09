<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Текстовые поля наполнения: простой текст экранируется (переносы строк сохраняются),
 * текст с тегами очищается по белому списку (админка хранит HTML из редактора и вставок из Word).
 */
class RichText
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function render(?string $value): HtmlString
    {
        $value = trim((string) $value);
        if ($value === '') {
            return new HtmlString('');
        }
        if (! preg_match('/<\/?[a-z][^>]*>/i', $value)) {
            return new HtmlString(nl2br(e($value)));
        }

        // Элементы <head> (style, title, template…) санитайзер в контексте body не отбрасывает, а снимает
        // с сохранением текста, поэтому убираем их вместе с содержимым заранее.
        $value = preg_replace('~<(?P<t>script|style|head|title|template|noscript|xml)\b[^>]*>.*?</(?P=t)\s*>~is', '', $value) ?? '';

        return new HtmlString(self::sanitizer()->sanitizeFor('body', $value));
    }

    /** Текст без разметки в одну строку — для превью внутри ссылок и мета-описаний. */
    public static function plain(?string $value): string
    {
        $html = preg_replace('~</p>|<br\s*/?>|</li>~i', ' ', self::render($value)->toHtml());

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private static function sanitizer(): HtmlSanitizer
    {
        return self::$sanitizer ??= new HtmlSanitizer(self::config());
    }

    private static function config(): HtmlSanitizerConfig
    {
        // Неизвестные теги (Word, редакторы) снимаются, текст внутри остаётся.
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->allowElement('p')->allowElement('br')->allowElement('strong')->allowElement('b')
            ->allowElement('em')->allowElement('i')->allowElement('ul')->allowElement('ol')->allowElement('li')
            ->allowElement('a', ['href'])
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks(false)
            ->forceAttribute('a', 'rel', 'noopener');
        // Содержимое этих элементов не должно попадать на страницу вовсе.
        foreach (['script', 'style', 'head', 'title', 'template', 'iframe', 'object', 'embed', 'svg', 'math', 'noscript', 'meta', 'link', 'xml'] as $tag) {
            $config = $config->dropElement($tag);
        }

        return $config;
    }
}
