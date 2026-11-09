<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
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
        $config = (new HtmlSanitizerConfig)
            ->allowElement('p')->allowElement('br')->allowElement('strong')->allowElement('b')
            ->allowElement('em')->allowElement('i')->allowElement('ul')->allowElement('ol')->allowElement('li')
            ->allowElement('a', ['href'])
            ->dropElement('script')->dropElement('style')
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks(false)
            ->forceAttribute('a', 'rel', 'noopener');
        // Остальная разметка (Word, редакторы) снимается, текст внутри остаётся.
        foreach (['span', 'font', 'div', 'u', 's', 'strike', 'sub', 'sup', 'small', 'mark', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'thead', 'tbody', 'tr', 'td', 'th', 'section', 'article', 'pre', 'code'] as $tag) {
            $config = $config->blockElement($tag);
        }

        return self::$sanitizer ??= new HtmlSanitizer($config);
    }
}
