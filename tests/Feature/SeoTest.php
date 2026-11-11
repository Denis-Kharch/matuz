<?php

use App\Content\ContentImporter;
use App\Content\ContentRepository;
use App\Content\DatabaseContentRepository;
use App\Models\CaseStudy;
use App\Support\OgImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return list<array> все блоки JSON-LD страницы */
function jsonLd(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

    return array_map(fn ($j) => json_decode($j, true, flags: JSON_THROW_ON_ERROR), $m[1]);
}

function ldTypes(array $blocks): array
{
    return collect($blocks)->flatMap(fn ($b) => $b['@graph'] ?? [$b])->pluck('@type')->all();
}

it('home describes the person, the service and the site in JSON-LD', function () {
    $blocks = jsonLd($this->get('/')->getContent());
    expect($blocks)->toHaveCount(1)->and(ldTypes($blocks))->toBe(['WebSite', 'Person', 'ProfessionalService']);
    $person = collect($blocks[0]['@graph'])->firstWhere('@type', 'Person');
    $contacts = app(ContentRepository::class)->contacts();
    expect($person['name'])->toBe('Андрей Матузов')
        ->and($person['telephone'])->toBe('+79186428895')
        ->and($person['sameAs'])->toContain($contacts['vk'], $contacts['telegram'])
        ->and($person['address']['addressLocality'])->toBe('Санкт-Петербург');
});

it('case page is an article with breadcrumbs and its own JPG preview', function () {
    $case = app(ContentRepository::class)->cases()[0];
    $html = $this->get('/cases/'.$case['slug'])->getContent();
    $graph = jsonLd($html)[0]['@graph'];
    $article = collect($graph)->firstWhere('@type', 'Article');
    $crumbs = collect($graph)->firstWhere('@type', 'BreadcrumbList')['itemListElement'];
    expect($article['headline'])->toBe($case['title'])
        ->and($article['url'])->toBe(route('cases.show', $case['slug']))
        ->and(array_column($crumbs, 'name'))->toBe(['Главная', 'Кейсы', $case['title']]);

    $og = OgImage::url($case['cover']);
    // Превью кейса — из его обложки.
    expect($og)->toEndWith('.jpg')->and($html)->toContain('<meta property="og:image" content="'.$og.'">');
    [$w, $h, $type] = getimagesize(public_path(parse_url($og, PHP_URL_PATH)));
    expect([$w, $h, $type])->toBe([1200, 630, IMAGETYPE_JPEG]);
});

it('events page has breadcrumbs', function () {
    expect(ldTypes(jsonLd($this->get('/events')->getContent())))->toBe(['BreadcrumbList']);
});

it('verification codes and metrika are printed only when set', function () {
    $this->get('/')->assertDontSee('yandex-verification', false)->assertDontSee('google-site-verification', false)->assertDontSee('name="ym-id"', false);
    config(['site.seo' => ['yandex_verification' => 'y123', 'google_verification' => 'g456', 'metrika_id' => '777']]);
    $this->get('/')->assertSee('<meta name="yandex-verification" content="y123">', false)
        ->assertSee('<meta name="google-site-verification" content="g456">', false)
        ->assertSee('<meta name="ym-id" content="777">', false);
});

it('sitemap has lastmod from the latest content change in database mode only', function () {
    $this->get('/sitemap.xml')->assertDontSee('<lastmod>', false);

    app(ContentImporter::class)->importIfEmpty();
    config(['site.content_source' => 'database']);
    app()->forgetInstance(ContentRepository::class);
    CaseStudy::query()->first()->forceFill(['updated_at' => '2030-01-02 03:04:05'])->saveQuietly();

    expect(app(DatabaseContentRepository::class)->lastModified())->toStartWith('2030-01-02T03:04:05');
    $this->get('/sitemap.xml')->assertSee('<lastmod>2030-01-02T03:04:05', false);
});
