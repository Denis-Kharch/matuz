<?php

namespace App\Site;

use App\Content\ContentRepository;
use App\Support\OgImage;
use App\Support\RichText;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Микроразметка schema.org (JSON-LD) для поисковиков: кто такой Андрей, чем занимается, как связаться;
 * у кейса — статья с автором и «хлебными крошками».
 */
class StructuredData
{
    public function __construct(private ContentRepository $c) {}

    public function home(): array
    {
        return ['@context' => 'https://schema.org', '@graph' => [$this->website(), $this->person(), $this->service()]];
    }

    /** @param array<string, mixed> $case */
    public function case(array $case): array
    {
        $url = route('cases.show', $case['slug']);

        return ['@context' => 'https://schema.org', '@graph' => [
            $this->person(),
            array_filter([
                '@type' => 'Article',
                '@id' => $url.'#article',
                'url' => $url,
                'mainEntityOfPage' => $url,
                'headline' => Str::limit($case['title'], 110, ''),
                'description' => Str::limit(RichText::plain($case['result']), 300),
                'image' => OgImage::url($case['cover']),
                'inLanguage' => 'ru',
                'author' => ['@id' => $this->id('person')],
                'publisher' => ['@id' => $this->id('person')],
                'about' => $case['client'] ? ['@type' => 'Organization', 'name' => $case['client']] : null,
            ]),
            $this->breadcrumbs([
                ['Главная', url('/')],
                ['Кейсы', url('/').'#cases'],
                [$case['title'], $url],
            ]),
        ]];
    }

    public function events(): array
    {
        return ['@context' => 'https://schema.org', '@graph' => [
            $this->breadcrumbs([['Главная', url('/')], ['Лента событий', route('events.index')]]),
        ]];
    }

    /** <script type="application/ld+json"> — с экранированием «</» и без лишних слэшей. */
    public static function script(array $data): HtmlString
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_PRETTY_PRINT);

        return new HtmlString('<script type="application/ld+json">'.$json.'</script>');
    }

    private function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => $this->id('website'),
            'url' => url('/'),
            'name' => 'Андрей Матузов',
            'inLanguage' => 'ru',
            'publisher' => ['@id' => $this->id('person')],
        ];
    }

    private function person(): array
    {
        $about = $this->c->about();
        $contacts = $this->c->contacts();

        return array_filter([
            '@type' => 'Person',
            '@id' => $this->id('person'),
            'name' => 'Андрей Матузов',
            'jobTitle' => 'Бизнес-тренер, T&D-партнёр, спикер',
            'description' => $about['lead'] ?? null,
            'url' => url('/'),
            'image' => OgImage::url($about['photo'] ?? null) ?? OgImage::url($this->c->hero()['image']),
            'telephone' => $this->phone($contacts),
            'address' => $this->address(),
            'sameAs' => array_values(array_filter([$contacts['vk'] ?? null, $contacts['telegram'] ?? null, $contacts['max'] ?? null])),
            'knowsAbout' => ['Корпоративное обучение', 'Обучение и развитие персонала (T&D, L&D)', 'Тренинги продаж', 'Переговоры',
                'Управленческие компетенции', 'Наставничество', 'Стратегические сессии', 'Фасилитация'],
        ]);
    }

    private function service(): array
    {
        $contacts = $this->c->contacts();

        return array_filter([
            '@type' => 'ProfessionalService',
            '@id' => $this->id('service'),
            'name' => 'Андрей Матузов — корпоративное обучение и мероприятия',
            'description' => 'Корпоративное обучение под задачи бизнеса: анализ, разработка, тренинги по продажам, переговорам и управлению, '
                .'организация стратегических сессий и форумов.',
            'url' => url('/'),
            'image' => OgImage::url($this->c->hero()['image']),
            'telephone' => $this->phone($contacts),
            'address' => $this->address(),
            'areaServed' => ['@type' => 'Country', 'name' => 'Россия'],
            'founder' => ['@id' => $this->id('person')],
            'sameAs' => array_values(array_filter([$contacts['vk'] ?? null, $contacts['telegram'] ?? null])),
        ]);
    }

    /** @param list<array{0:string, 1:string}> $items */
    private function breadcrumbs(array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn ($item, $i) => [
                '@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => $item[1],
            ], $items, array_keys($items)),
        ];
    }

    private function address(): array
    {
        return ['@type' => 'PostalAddress', 'addressLocality' => 'Санкт-Петербург', 'addressCountry' => 'RU'];
    }

    private function phone(array $contacts): ?string
    {
        $href = (string) ($contacts['phone_href'] ?? '');

        return str_starts_with($href, 'tel:') ? substr($href, 4) : null;
    }

    private function id(string $name): string
    {
        return url('/').'#'.$name;
    }
}
