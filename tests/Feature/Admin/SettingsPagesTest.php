<?php

use App\Content\ContentImporter;
use App\Filament\Pages\AboutSettings;
use App\Filament\Pages\ContactsSettings;
use App\Filament\Pages\HeroSettings;
use App\Filament\Pages\KpSettings;
use App\Filament\Pages\SiteSettings;
use App\Models\Media;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\TelegramNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->publicDir = sys_get_temp_dir().'/settings-test-'.bin2hex(random_bytes(4));
    mkdir($this->publicDir);
    $this->originalPublic = public_path();
    app()->usePublicPath($this->publicDir);
    $this->withoutVite();
    app(ContentImporter::class)->importIfEmpty();
    config(['site.content_source' => 'database']);
    $this->actingAs(User::factory()->create());
});

afterEach(function () {
    app()->usePublicPath($this->originalPublic);
    File::deleteDirectory($this->publicDir);
});

it('hero page saves texts and keeps the photo', function () {
    $key = Media::where('slot', 'hero')->value('key');

    Livewire::test(HeroSettings::class)
        ->assertFormSet(['title' => Setting::get('hero')['title'], 'subject_side' => 'left'])
        ->fillForm(['title' => 'Новый заголовок первого экрана', 'subtitle' => 'Новый подзаголовок', 'note' => 'Новое примечание', 'subject_side' => 'right'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('hero'))->toMatchArray(['title' => 'Новый заголовок первого экрана', 'subject_side' => 'right'])
        ->and(Media::where('slot', 'hero')->value('key'))->toBe($key);
    $this->get('/')->assertOk()->assertSee('Новый заголовок первого экрана')->assertSee('Новое примечание');
});

it('about page saves paragraphs, city, training intro', function () {
    Livewire::test(AboutSettings::class)
        ->fillForm([
            'name' => 'Матузов Андрей Тестович',
            'lead' => 'Новая вводная',
            'paragraphs' => [['paragraph' => '<p>Первый <b>абзац</b></p>'], ['paragraph' => '<p>Второй абзац</p>']],
            'city' => 'Москва, Россия',
            'training_intro' => 'Новое вступление к обучению',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('about')['paragraphs'])->toHaveCount(2)
        ->and(Setting::get('about')['paragraphs'])->toBeList()
        ->and(Media::where('slot', 'about')->exists())->toBeTrue();
    $this->get('/')->assertOk()
        ->assertSee('Матузов Андрей Тестович')->assertSee('Новая вводная')
        ->assertSee('Второй абзац')->assertSee('Москва, Россия')->assertSee('Новое вступление к обучению');
});

it('contacts page builds the tel link and validates urls', function () {
    Livewire::test(ContactsSettings::class)
        ->fillForm(['phone' => '8 (900) 111-22-33', 'vk' => 'не ссылка'])
        ->call('save')
        ->assertHasFormErrors(['vk']);

    Livewire::test(ContactsSettings::class)
        ->fillForm(['phone' => '+7 900 111 22 33', 'vk' => 'https://vk.ru/test', 'telegram' => 'https://t.me/test', 'max' => 'https://max.ru/u/test'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('contacts'))->toBe([
        'phone' => '+7 900 111 22 33', 'phone_href' => 'tel:+79001112233',
        'vk' => 'https://vk.ru/test', 'telegram' => 'https://t.me/test', 'max' => 'https://max.ru/u/test',
    ]);
    $this->get('/')->assertOk()->assertSee('tel:+79001112233', false)->assertSee('+7 900 111 22 33');
    expect(ContactsSettings::phoneHref('8 (918) 642-88-95'))->toBe('tel:+79186428895');
});

it('kp page saves all blocks and the document keeps rendering', function () {
    $before = Setting::get('kp');
    Livewire::test(KpSettings::class)
        ->assertFormSet(['subtitle' => $before['subtitle']])
        ->fillForm([
            'subtitle' => 'Новый подзаголовок КП',
            'formats' => ['Лекция', 'Воркшоп'],
            'prices' => [['label' => 'Новая услуга', 'price' => '99 ₽']],
            'price_factors' => [['title' => 'Фактор', 'text' => 'Описание фактора']],
            'topics' => [['title' => 'Новая группа', 'items' => [['item' => 'Тема один'], ['item' => 'Тема два']]]],
            'achievements' => [['achievement' => 'Достижение один'], ['achievement' => 'Достижение два']],
            'final' => 'Финальная фраза КП',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $kp = Setting::get('kp');
    expect($kp['prices'])->toBe([['label' => 'Новая услуга', 'price' => '99 ₽']])
        ->and($kp['topics'])->toBe([['title' => 'Новая группа', 'items' => ['Тема один', 'Тема два']]])
        ->and($kp['formats'])->toBe(['Лекция', 'Воркшоп'])
        ->and($kp['achievements'])->toBe(['Достижение один', 'Достижение два'])
        ->and(array_keys($kp))->toEqualCanonicalizing(array_keys($before))
        ->and($kp['why'])->toBe($before['why']);
    $this->get('/')->assertOk()->assertSee('Новый подзаголовок КП')->assertSee('Новая услуга')
        ->assertSee('Тема два')->assertSee('Финальная фраза КП');
});

it('site page: noindex follows the setting in database mode', function () {
    config(['site.preview' => true]);
    Livewire::test(SiteSettings::class)->assertFormSet(['noindex' => true]);
    $this->get('/')->assertSee('noindex, nofollow', false);
    $this->get('/robots.txt')->assertSee('Disallow: /');

    Livewire::test(SiteSettings::class)->fillForm(['noindex' => false])->call('save')->assertHasNoFormErrors();

    $this->get('/')->assertOk()->assertDontSee('noindex', false);
    $this->get('/robots.txt')->assertSee('Allow: /')->assertDontSee('Disallow');

    // Статичный источник по-прежнему управляется конфигом.
    config(['site.content_source' => 'static', 'site.preview' => true]);
    $this->get('/')->assertSee('noindex, nofollow', false);
});

it('site page keeps the stored token when the field is left empty and never shows it', function () {
    Setting::put('telegram', ['token' => 'SECRET:token', 'chat_id' => '1']);

    $page = Livewire::test(SiteSettings::class)->assertFormSet(['token' => null, 'chat_id' => '1'])
        ->assertDontSee('SECRET:token')
        ->fillForm(['chat_id' => '42'])->call('save');
    expect(Setting::get('telegram'))->toBe(['token' => 'SECRET:token', 'chat_id' => '42']);

    $page->fillForm(['token' => 'NEW:token'])->call('save');
    expect(Setting::get('telegram')['token'])->toBe('NEW:token');
});

it('telegram test button posts to the bot api', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    Setting::put('telegram', ['token' => '123:ABC', 'chat_id' => '777']);

    Livewire::test(SiteSettings::class)->callAction('telegramTest');

    Http::assertSent(fn ($r) => $r->url() === 'https://api.telegram.org/bot123:ABC/sendMessage'
        && $r['chat_id'] === '777' && str_contains($r['text'], 'Тестовое'));
});

it('telegram notifier returns false on missing settings, never sends', function () {
    Http::fake();
    expect(TelegramNotifier::send('x'))->toBeFalse();
    Http::assertNothingSent();
});

it('telegram notifier returns false on api error', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 401)]);
    Setting::put('telegram', ['token' => 'T', 'chat_id' => '1']);
    expect(TelegramNotifier::send('x'))->toBeFalse();
});

it('telegram notifier returns false when the request throws', function () {
    Http::fake(['api.telegram.org/*' => fn () => throw new RuntimeException('timeout')]);
    Setting::put('telegram', ['token' => 'T', 'chat_id' => '1']);
    expect(TelegramNotifier::send('x'))->toBeFalse();
});

it('telegram notifier returns true on success', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    Setting::put('telegram', ['token' => 'T', 'chat_id' => '1']);
    expect(TelegramNotifier::send('x'))->toBeTrue();
});

it('settings pages are in the navigation groups', function () {
    expect(HeroSettings::getNavigationGroup())->toBe('Страница')
        ->and(AboutSettings::getNavigationGroup())->toBe('Страница')
        ->and(ContactsSettings::getNavigationGroup())->toBe('Страница')
        ->and(KpSettings::getNavigationGroup())->toBe('КП')
        ->and(SiteSettings::getNavigationGroup())->toBe('Настройки');
    $this->get('/admin/site')->assertOk();
});
