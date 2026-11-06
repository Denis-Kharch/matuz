# Этап 1 — дизайн страницы: план реализации

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Премиальная тёмная одностраничная витрина Андрея Матузова со всеми блоками ТЗ и работающим «моментальным КП» (форма → персональное КП → PDF), опубликованная на GitHub Pages как статичная выгрузка Laravel-сайта.

**Architecture:** Laravel без базы данных. Всё наполнение отдаёт интерфейс `ContentRepository`. На этапе 1 его реализует `StaticContentRepository`, который читает PHP-массивы из `resources/content/`. На этапе 3 его заменит реализация на базе данных, а шаблоны останутся прежними. Страницы собираются из Blade-шаблонов с Tailwind v4 и Alpine.js. Пакет `spatie/laravel-export` выгружает сайт в статичный HTML, а GitHub Actions публикует его на Pages.

**Tech Stack:** PHP 8.3, Laravel (последняя стабильная), Blade, Tailwind CSS v4 (`@tailwindcss/vite`), Alpine.js 3 + `@alpinejs/focus`, Vite, Pest, `spatie/laravel-export`, `sharp` (нарезка фото), `@fontsource/*` (шрифты), html2pdf.js 0.10.1 (PDF), Playwright + `@axe-core/playwright` (браузерные тесты), `node --test` (юнит-тесты JS).

**Spec:** `docs/superpowers/specs/2026-11-06-matuzov-site-design.md`

## Global Constraints

- **Язык и шрифты.** Весь интерфейс только на русском. Шрифты обязаны поддерживать кириллицу и подключаются с собственного хостинга, не с Google.
- **Данные посетителя.** Форма КП: «Название компании» обязательно, «Город» необязательно. Никаких полей телефона, e-mail или имени.
- **Надпись над формой КП** дословно: «Прочитав коммерческое предложение — вы уже на полпути к результатам».
- **Палитра:**
  - тёплые: #ED9954, #F4AC5C;
  - бирюзовые: #076465, #5CA4A7;
  - песочный: #CCB494;
  - тёмно-коричневый: #52472E.
  - Тема тёмная. Контраст текста — WCAG AA (≥ 4.5:1 для обычного текста, ≥ 3:1 для крупного).
- **Ссылки в HTML** только через `url()`, `route()`, `asset()` или `Vite`. Никаких путей от корня вида `"/img/..."`: на Pages сайт живёт в подпапке `/<repo>/`.
- **Движение:** всё уважает `prefers-reduced-motion: reduce`. Без 3D, шейдеров и тяжёлых библиотек анимации.
- **Ширины проверки:** 375, 768, 1280, 1440. Горизонтальной прокрутки нет ни на одной из них.
- **Контакты:**
  - телефон: `+7 918 642 88 95` (`tel:+79186428895`);
  - VK: `https://vk.ru/matuzovtrener`;
  - Telegram: `https://t.me/a_matuzov`;
  - MAX: ссылка из `_source/Сайт Матузов.html`.
- **Демо-кейсы и демо-события** из чернового HTML вымышленные. В данных они помечаются полем `'demo' => true` и до запуска (этап 3) заменяются реальными.
- **Внешний вид.** Каждый визуальный таск выполняется с загруженными скиллами `design-taste-frontend`, `impeccable`, `ui-ux-pro-max`. Референсы watermelon (https://ui.watermelon.sh/blocks/hero, /blocks/faq, /blocks/blog) — образец композиции и поведения, а не код для копирования.
- **Коммиты** заканчиваются строкой `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

## Review Focus

1. **Компания с кавычками, «ёлочками», эмодзи, `/\:*?"<>|` или длиной 200+ символов.**
   - КП показывает название как есть, но экранированным.
   - Имя PDF очищено и не длиннее 60 символов.
   - Если после очистки ничего не осталось, файл называется `КП_Матузов.pdf`.
   - Тесты: Task 11.
2. **Прямой переход на `/cases/<slug>` или `/events/<slug>` и кнопка «Назад» браузера.**
   - Оверлей открыт сразу при загрузке.
   - «Назад» или Esc закрывает его и возвращает адрес главной.
   - Несуществующий slug отдаёт 404.
   - Тесты: Task 8 и Task 9.
3. **Сайт в подпапке GitHub Pages.** Ни одна ссылка и ни один ресурс не начинаются с `/` без базового пути. Тест: Task 12, скрипт проверки выгрузки.
4. **Ширина 375 px.**
   - Нет горизонтальной прокрутки.
   - Лицо на фото первого экрана в кадре.
   - Плавающая кнопка КП не перекрывает кнопку отправки формы КП и кнопки окна cookie: она скрыта, пока открыто любое окно.
   - Тесты: Task 4 и Task 13.
5. **html2pdf.js не загрузился** (блокировщик или нет сети). Кнопка «Скачать PDF» вызывает `window.print()` со стилями для печати, а не молча ничего не делает. Тест: Task 11.

---

## Структура файлов

```
_source/                         исходники заказчика (черновой HTML, палитра, фото) — не публикуются
app/Content/ContentRepository.php            интерфейс наполнения
app/Content/StaticContentRepository.php      реализация на PHP-массивах
app/Http/Controllers/PageController.php      home, case, events, event
resources/content/*.php                      данные: hero, principles, about, experience, formats,
                                             topics, event-services, cases, events, partners, faq,
                                             gallery, certificates, contacts, kp
resources/css/app.css                        Tailwind v4 + токены @theme + шрифты
resources/js/app.js                          Alpine + регистрация модулей
resources/js/ui/{modal,scrollspy,lightbox,cookie,overlay-route}.js
resources/js/kp/{kp-form,file-name,lead-sink,pdf}.js
resources/views/layouts/site.blade.php
resources/views/components/{button,kp-button,section,picture,modal}.blade.php
resources/views/partials/{header,floating-kp,cookie,footer}.blade.php
resources/views/sections/{hero,marquee,about,directions,cases,events,partners,faq,gallery,certificates,contacts}.blade.php
resources/views/kp/{form,document}.blade.php
resources/views/pages/{home,events}.blade.php
resources/images/                            исходные фото (крупные)
scripts/images.mjs                           нарезка фото → public/img (webp/avif, 640/1280/1920)
scripts/check-export.mjs                     проверка статичной выгрузки
tests/Feature/*.php                          Pest
tests/js/*.test.mjs                          node --test
tests/e2e/*.spec.ts                          Playwright
.github/workflows/pages.yml
DESIGN.md                                    дизайн-система (токены, шрифты, компоненты)
```

---

### Task 1: Окружение и каркас проекта

**Files:**
- Create: Laravel-проект в корне репозитория, `.gitignore`, `_source/`, `resources/images/`
- Test: `tests/Feature/SmokeTest.php`

**Interfaces:**
- Produces: работающие `php artisan serve`, `npm run dev`, `npm run build`, `./vendor/bin/pest`. Tailwind v4 и Alpine подключены через `resources/js/app.js` и `resources/css/app.css`.

- [ ] **Step 1: Включить расширения PHP.** `php --ini` → в найденном `php.ini` раскомментировать `extension=intl`, `zip`, `pdo_sqlite`, `pdo_mysql`, `fileinfo`, `gd`, `mbstring`, `openssl`.
  Проверка: `php -m` содержит `intl`, `zip`, `pdo_sqlite`.
- [ ] **Step 2: Установить Composer** (`winget install Composer.Composer` или `composer-setup.php` с getcomposer.org).
  Проверка: `composer --version`.
- [ ] **Step 3: Создать проект.** `composer create-project laravel/laravel _app` во временной папке, затем перенести содержимое `_app` в корень репозитория, не трогая `.claude/` и `docs/`.
  - Черновые материалы перенести в `_source/`: `Сайт Матузов.html`, `b06f572b-…jpg` (палитра). Фото `exec-*.png` перенести в `resources/images/drafts/` под именами `stage.png` (3987…), `training.png` (4434…), `arena.png` (62b8…).
- [ ] **Step 4: Настроить `.env` и `.env.example`.**
  - `APP_NAME="Андрей Матузов"`, `APP_LOCALE=ru`.
  - `SESSION_DRIVER=file`, `CACHE_STORE=file`, `DB_CONNECTION=sqlite`. Базы на этапе 1 нет, миграции не запускаются.
- [ ] **Step 5: Подключить фронтенд.**
  - `npm i -D tailwindcss @tailwindcss/vite` и подключить плагин в `vite.config.js`. В `app.css` — `@import "tailwindcss";`.
  - `npm i alpinejs @alpinejs/focus`; `app.js` запускает Alpine с плагином focus.
- [ ] **Step 6: Установить Pest** (`composer require pestphp/pest --dev --with-all-dependencies`, `./vendor/bin/pest --init`).
  Написать `SmokeTest`:

```php
it('serves the home page', function () {
    $this->get('/')->assertOk();
});
```

- [ ] **Step 7: Запуск.** `./vendor/bin/pest` → PASS.
- [ ] **Step 8: git.** `git init`, `git branch -M main`. В `.gitignore` к стандартному Laravel добавить `/dist`, `/test-results`, `/playwright-report`. Коммит `chore: scaffold Laravel project`.

---

### Task 2: Слой наполнения

**Files:**
- Create: `app/Content/ContentRepository.php`, `app/Content/StaticContentRepository.php`, `resources/content/*.php` (15 файлов из структуры выше)
- Modify: `app/Providers/AppServiceProvider.php` (биндинг)
- Test: `tests/Feature/ContentTest.php`

**Interfaces:**
- Produces: интерфейс `App\Content\ContentRepository` (все методы возвращают массивы). Синглтон в контейнере: `ContentRepository::class → StaticContentRepository::class`.

```php
public function hero(): array;          // title, subtitle, note, image, image_mobile|null, focal['x','y'] (0–100), subject_side 'left'|'right'
public function principles(): array;    // list<string>
public function about(): array;         // name, lead, paragraphs list<string>, city, photo
public function experience(): array;    // list<{title, period|null, text, image}>
public function trainingIntro(): string;
public function formats(): array;       // list<{n, title, tags list<string>, description}>
public function topicGroups(): array;   // list<{title, items list<{title, text}>}>
public function eventServices(): array; // list<{title, text}>
public function cases(): array;         // list<{slug, direction 'training'|'events', title, date, people, request, plan, result, review, photos list<string>, demo bool}>
public function findCase(string $slug): ?array;
public function events(?int $limit = null): array; // list<{slug, title, date 'Y-m-d', client, description, photos list<string>, demo bool}>, по дате ↓
public function findEvent(string $slug): ?array;
public function partners(): array;      // list<{name, logo, description, url|null}>
public function faq(): array;           // list<{q, a}>
public function gallery(): array;       // list<{image, caption}>
public function certificates(): array;  // list<{image, title, year int}>
public function contacts(): array;      // phone, phone_href, vk, telegram, max
public function kp(): array;            // why, who, focus, formats list<string>, prices list<{label, price}>, terms, topics list<{title, items list<string>}>, achievements list<string>, final
```

Пути картинок в данных — имена без расширения относительно `public/img/` (например `drafts/stage`). Их разворачивает компонент `<x-picture>` из Task 3.

- [ ] **Step 1: Написать падающий `ContentTest`** с точными значениями из ТЗ:

```php
beforeEach(fn () => $this->c = app(\App\Content\ContentRepository::class));

it('has hero copy from the brief', function () {
    expect($this->c->hero()['title'])->toBe('Андрей Матузов — ваш деловой партнёр в организации эффективного обучения')
        ->and($this->c->hero()['subtitle'])->toBe('От анализа и разработки до проведения и результата')
        ->and($this->c->hero()['subject_side'])->toBeIn(['left', 'right']);
});
it('has 8 principles numbered consecutively', fn () =>
    expect($this->c->principles())->toHaveCount(8)
        ->and($this->c->principles()[0])->toStartWith('Принцип №1')
        ->and($this->c->principles()[7])->toStartWith('Принцип №8'));
it('has 4 experience stages', fn () => expect($this->c->experience())->toHaveCount(4));
it('has 8 formats', fn () => expect($this->c->formats())->toHaveCount(8));
it('has 5 topic groups with 4–8 items', function () {
    expect($this->c->topicGroups())->toHaveCount(5);
    foreach ($this->c->topicGroups() as $g) expect(count($g['items']))->toBeGreaterThanOrEqual(4)->toBeLessThanOrEqual(8);
});
it('has 7 event services', fn () => expect($this->c->eventServices())->toHaveCount(7));
it('has 8 cases, both directions, exactly 3 photos each, unique slugs', function () {
    $cases = $this->c->cases();
    expect($cases)->toHaveCount(8)
        ->and(collect($cases)->pluck('direction')->unique()->sort()->values()->all())->toBe(['events', 'training'])
        ->and(collect($cases)->pluck('slug')->unique())->toHaveCount(8);
    foreach ($cases as $case) expect($case['photos'])->toHaveCount(3);
});
it('sorts events newest first and limits', function () {
    $e = $this->c->events();
    expect($e[0]['date'] >= $e[1]['date'])->toBeTrue()
        ->and($this->c->events(6))->toHaveCount(min(6, count($e)));
    foreach ($e as $ev) expect(count($ev['photos']))->toBeGreaterThanOrEqual(3)->toBeLessThanOrEqual(6);
});
it('finds by slug or returns null', function () {
    expect($this->c->findCase($this->c->cases()[0]['slug']))->not->toBeNull()
        ->and($this->c->findCase('nope'))->toBeNull()
        ->and($this->c->findEvent('nope'))->toBeNull();
});
it('has contacts', fn () => expect($this->c->contacts())->toMatchArray([
    'phone' => '+7 918 642 88 95', 'phone_href' => 'tel:+79186428895',
    'vk' => 'https://vk.ru/matuzovtrener', 'telegram' => 'https://t.me/a_matuzov',
]));
```

- [ ] **Step 2: Запуск.** `./vendor/bin/pest tests/Feature/ContentTest.php` → FAIL: класса нет.
- [ ] **Step 3: Реализовать интерфейс, `StaticContentRepository` и биндинг.** Каждый метод делает `require resource_path("content/<name>.php")`. `events()` сортирует по `date` по убыванию и применяет `$limit`.
- [ ] **Step 4: Заполнить `resources/content/*.php`.**
  - Тексты из спецификации §6 и ТЗ: hero, принципы, «Об Андрее», 4 этапа опыта, вступление «Обучение и спикерство», названия тем, мероприятия, надписи блоков.
  - Описания форматов, тем и мероприятий, кейсы, ленту, партнёров, FAQ, галерею, регалии и КП — из JS-массивов `FORMATS`, `TOPICS`, `EVENTS`, `CASES`, `FEED`, `PARTNERS`, `FAQ`, `GALLERY`, `CERTS` и блока `#kpPaper` в `_source/Сайт Матузов.html`.
  - Названия тем — по ТЗ: где ТЗ и черновик расходятся, побеждает ТЗ. Опечатки ТЗ исправить: «Принцип №1 — Обучение должно быть эффективным», «возражений», «руководителя» и т. п.
  - Принципы перенумеровать подряд 1–8.
  - Кейсов не хватает до 8 → добавить демо (`demo => true`), по 4 на направление.
  - Фото — `drafts/stage`, `drafts/training`, `drafts/arena` по кругу.
  - Hero: `image => 'drafts/stage'`, `subject_side => 'left'`, `focal => ['x' => 24, 'y' => 30]`.
- [ ] **Step 5: Запуск.** `./vendor/bin/pest` → PASS.
- [ ] **Step 6: Коммит** `feat: content repository with static data from brief`.

---

### Task 3: Дизайн-система и базовые компоненты

**Files:**
- Create: `DESIGN.md`, `scripts/images.mjs`, `resources/views/components/{button,kp-button,section,picture}.blade.php`, `tests/js/contrast.test.mjs`, `tests/js/tokens.mjs`
- Modify: `resources/css/app.css`, `package.json` (скрипты `images`, `test:js`)
- Test: `tests/js/contrast.test.mjs`, `tests/Feature/ComponentsTest.php`

**Interfaces:**
- Produces:
  - CSS-токены в `@theme`: `--color-bg`, `--color-surface`, `--color-surface-2`, `--color-line`, `--color-text`, `--color-muted`, `--color-accent` (#ED9954), `--color-accent-2` (#F4AC5C), `--color-teal` (#076465), `--color-teal-2` (#5CA4A7), `--color-sand` (#CCB494), `--color-umber` (#52472E); шрифты `--font-display`, `--font-body`.
  - `tests/js/tokens.mjs` экспортирует те же цвета объектом `tokens` — единый источник для теста контраста.
  - `<x-button variant="primary|quiet|ghost" href? type?>`.
  - `<x-kp-button source="hero" variant="primary|quiet">Получить моментальное КП</x-kp-button>` рендерит `<button data-kp-source="hero" @click="$dispatch('kp-open', {source: 'hero'})">`.
  - `<x-section id title kicker?>`.
  - `<x-picture src="drafts/stage" alt sizes? :eager="false" :focal="['x'=>..,'y'=>..]">` → `<picture>` с avif и webp, `srcset` 640/1280/1920 из `public/img/<src>-<w>.<ext>`, `loading="lazy"` (кроме eager), `object-position` из focal.
  - `npm run images`: `scripts/images.mjs` нарезает всё из `resources/images/**` в `public/img/**` (sharp; avif q50, webp q72; ширины 640/1280/1920, не больше исходника). Повторный запуск пропускает свежие файлы.

- [ ] **Step 1: Загрузить скиллы** `design-taste-frontend`, `impeccable`, `ui-ux-pro-max`. По ним и по палитре подобрать токены: фон — почти чёрный с бирюзовым оттенком, поверхности, линии, текст и приглушённый текст. Записать решения в `DESIGN.md`: токены, роли цветов, шкала отступов, радиусы, тени, правила кнопок, принципы движения.
- [ ] **Step 2: Шрифты.**
  - Две пары с кириллицей через `@fontsource`. **Пара A:** Geologica (заголовки) + Golos Text (текст). **Пара B:** Cormorant Garamond (заголовки) + Manrope (текст).
  - Пара A по умолчанию, пара B включается атрибутом `<html data-fonts="b">`.
  - Переключатель «Шрифты A/B» показывается только при `config('site.preview') === true` (`SITE_PREVIEW=true`) и запоминает выбор в `localStorage`.
- [ ] **Step 3: Падающий тест контраста.**

```js
import { test } from 'node:test'; import assert from 'node:assert/strict';
import { tokens } from './tokens.mjs'; import { contrast } from './contrast-lib.mjs';
test('body text on bg ≥ 4.5', () => assert.ok(contrast(tokens.text, tokens.bg) >= 4.5));
test('muted text on bg ≥ 4.5', () => assert.ok(contrast(tokens.muted, tokens.bg) >= 4.5));
test('muted text on surface ≥ 4.5', () => assert.ok(contrast(tokens.muted, tokens.surface) >= 4.5));
test('text on accent button ≥ 4.5', () => assert.ok(contrast(tokens.onAccent, tokens.accent) >= 4.5));
test('teal-2 links on bg ≥ 4.5', () => assert.ok(contrast(tokens['teal-2'], tokens.bg) >= 4.5));
```

  `contrast-lib.mjs` — формула относительной яркости WCAG 2.x.
  Запуск: `node --test tests/js/` → FAIL, пока токены не заданы.
- [ ] **Step 4: Задать токены** в `app.css` и `tokens.mjs`. Запуск `node --test tests/js/` → PASS. Если пара не проходит — менять оттенок, а не тест.
- [ ] **Step 5: Компоненты и `npm run images`.** `ComponentsTest`:

```php
it('renders picture with avif+webp srcset and lazy', function () {
    $html = Blade::render('<x-picture src="drafts/stage" alt="Сцена" />');
    expect($html)->toContain('type="image/avif"')->toContain('drafts/stage-1280.webp')->toContain('loading="lazy"')
        ->not->toContain('src="/img'); // только через asset()
});
it('renders kp button with source', fn () =>
    expect(Blade::render('<x-kp-button source="cases">Получить моментальное КП</x-kp-button>'))
        ->toContain('data-kp-source="cases"'));
```

- [ ] **Step 6: Запуск.** `npm run images && ./vendor/bin/pest && node --test tests/js/` → PASS.
- [ ] **Step 7: Коммит** `feat: design tokens, fonts, base components, image pipeline`.

---

### Task 4: Каркас страницы — шапка, плавающая КП, cookie, модальные окна

**Files:**
- Create: `resources/views/layouts/site.blade.php`, `resources/views/partials/{header,floating-kp,cookie,footer}.blade.php`, `resources/views/components/modal.blade.php`, `resources/js/ui/{modal,scrollspy,cookie}.js`, `resources/views/pages/home.blade.php`, `app/Http/Controllers/PageController.php`, `routes/web.php`, `playwright.config.ts`
- Test: `tests/Feature/LayoutTest.php`, `tests/e2e/layout.spec.ts`

**Interfaces:**
- Consumes: `ContentRepository`, компоненты Task 3.
- Produces:
  - Маршрут `home` → `PageController@home`. Вид `pages.home` подключает `sections.*` по порядку §6 (секции, которых ещё нет, подключаются через `@includeIf`).
  - `<x-modal name="..." :fullscreen="false">` и Alpine-стор `Alpine.store('modals')`:
    - `open(name)`, `close(name)`, `closeTop()`, `closeAll()`, `isOpen(name)`, `any` (getter);
    - стек окон, Esc закрывает верхнее, клик по фону закрывает (кроме окна cookie);
    - на `<body>` вешается `overflow:hidden`, фокус удерживается внутри (`x-trap.inert.noscroll`) и возвращается на кнопку, которая открыла окно.
  - Якоря секций: `about`, `directions`, `cases`, `events`, `partners`, `faq`, `gallery`, `certificates`, `contacts`.
  - Пункты меню: «Об Андрее», «Направления», «Кейсы», «События», «Партнёры», «Вопросы», «Галерея», «Регалии», «Контакты».
- Playwright: `webServer` = `php artisan serve --port=8123`, `baseURL` `http://127.0.0.1:8123`. Проекты `desktop` (1280×800) и `mobile` (375×812, `isMobile`).

- [ ] **Step 1: Падающие тесты.**

```php
it('has nav links to every section', function () {
    $html = $this->get('/')->getContent();
    foreach (['about','directions','cases','events','partners','faq','gallery','certificates','contacts'] as $id)
        expect($html)->toContain('href="#'.$id.'"');
});
it('has the floating KP button', fn () => $this->get('/')->assertSee('data-kp-source="floating"', false));
```

```ts
test('mobile menu opens and closes on link click', async ({ page, isMobile }) => {
  test.skip(!isMobile); await page.goto('/');
  await page.getByRole('button', { name: 'Меню' }).click();
  await page.getByRole('link', { name: 'Кейсы' }).click();
  await expect(page.getByRole('navigation')).not.toBeInViewport(); // панель закрылась
});
test('cookie choice persists', async ({ page }) => {
  await page.goto('/'); await page.getByRole('button', { name: 'Принять' }).click();
  await page.reload(); await expect(page.getByRole('button', { name: 'Принять' })).toBeHidden();
});
test('floating KP hidden while any modal open', async ({ page }) => {
  await page.goto('/'); // cookie-окно открыто
  await expect(page.locator('[data-kp-source="floating"]')).toBeHidden();
});
test('no horizontal scroll', async ({ page }) => {
  await page.goto('/');
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
});
```

- [ ] **Step 2: Запуск.** `./vendor/bin/pest && npx playwright test tests/e2e/layout.spec.ts` → FAIL.
- [ ] **Step 3: Реализовать.**
  - Шапка: прозрачная над hero, при прокрутке становится плотной. Scrollspy на IntersectionObserver ставит `aria-current="true"` текущему пункту. На телефоне — кнопка «Меню» и выезжающая панель.
  - Плавающая кнопка: `x-show="!$store.modals.any"`.
  - Окно cookie по черновику: «Принять» / «Отказать и остаться», выбор хранится в `localStorage['am_cookie_choice']` (обёрнуто в try/catch). Варианта «Покинуть сайт» нет — решение исполнителя по спецификации §10.
  - Подвал: контакты и год.
  - Базовое SEO в `layouts/site`: `<html lang="ru">`, `@yield('title')` с запасным вариантом «Андрей Матузов — бизнес-тренер, T&D-партнёр, спикер · Санкт-Петербург», `meta description`, `link rel="canonical"`, Open Graph (`og:title`, `og:description`, `og:image` = кадр первого экрана 1280, `og:locale=ru_RU`). Превью ссылки в Telegram и VK должно выглядеть достойно.
  - В `LayoutTest` проверить, что на `/` есть `og:image` и `lang="ru"`.
- [ ] **Step 4: Запуск** тех же команд → PASS.
- [ ] **Step 5: Коммит** `feat: page shell, nav, floating KP, cookie, modal store`.

---

### Task 5: Первый экран и бегущая строка

**Files:**
- Create: `resources/views/sections/{hero,marquee}.blade.php`
- Test: `tests/Feature/HeroTest.php`, `tests/e2e/hero.spec.ts`

**Interfaces:**
- Consumes: `hero()`, `principles()`, `contacts()`, `<x-picture eager>`, `<x-kp-button source="hero">`.

- [ ] **Step 1: Падающие тесты.**

```php
it('renders hero copy, KP button and contacts', function () {
    $this->get('/')->assertSee('Андрей Матузов — ваш деловой партнёр в организации эффективного обучения')
        ->assertSee('От анализа и разработки до проведения и результата')
        ->assertSee('data-kp-source="hero"', false)->assertSee('tel:+79186428895', false);
});
it('puts copy opposite the subject', fn () => $this->get('/')->assertSee('data-copy-side="right"', false)); // subject_side=left
it('renders all principles in the marquee', function () {
    $r = $this->get('/');
    foreach (app(\App\Content\ContentRepository::class)->principles() as $p) $r->assertSee($p);
});
```

```ts
test('hero face stays in frame on mobile', async ({ page }) => {
  await page.goto('/');
  const pos = await page.locator('[data-hero-img] img').evaluate(el => getComputedStyle(el).objectPosition);
  expect(pos).toBe('24% 30%');
});
test('marquee stops with reduced motion', async ({ browser }) => {
  const ctx = await browser.newContext({ reducedMotion: 'reduce' }); const page = await ctx.newPage(); await page.goto('/');
  expect(await page.locator('[data-marquee-track]').evaluate(el => getComputedStyle(el).animationName)).toBe('none');
});
```

- [ ] **Step 2: Запуск** → FAIL.
- [ ] **Step 3: Реализовать hero.**
  - Фото на весь экран (`min-h-[100svh]`), затемняющий градиент со стороны текста.
  - Текст на стороне, противоположной `subject_side`.
  - Слева от текста — главная кнопка КП, справа — компактные контакты: телефон, VK, Telegram, MAX.
  - Под кнопкой — `note`: «Мы не собираем персональные данные — только название компании и город».
  - Если есть `image_mobile`, на `<640px` используется `<source media>`.
- [ ] **Step 4: Реализовать бегущую строку.**
  - Чистый CSS: дублированный трек, `translateX(-50%)`, разделитель «·».
  - Пауза при `:hover`, `animation: none` при reduced motion.
  - `aria-hidden` на дубле, полный список доступен вне анимации для экранных дикторов.
- [ ] **Step 5: Запуск** → PASS.
- [ ] **Step 6: Коммит** `feat: hero and principles marquee`.

---

### Task 6: «Кто такой Андрей Матузов» и «Опыт Андрея»

**Files:**
- Create: `resources/views/sections/about.blade.php`
- Test: `tests/Feature/AboutTest.php`

**Interfaces:**
- Consumes: `about()`, `experience()`, `<x-kp-button source="about" variant="quiet">`.

- [ ] **Step 1: Падающий тест.**

```php
it('renders about and 4 stages with images on the left', function () {
    $html = $this->get('/')->assertSee('Матузов Андрей')->assertSee('Санкт-Петербург')
        ->assertSee('Работа с персоналом')->assertSee('Первое касание с HR')
        ->assertSee('Новые горизонты в T&amp;D', false)->assertSee('Путь продолжается')
        ->assertSee('data-kp-source="about"', false)->getContent();
    expect(substr_count($html, 'data-stage-image="left"'))->toBe(4);
});
```

- [ ] **Step 2: Запуск** → FAIL.
- [ ] **Step 3: Реализовать.**
  - Фото слева в рамке в стиле сайта (тонкая песочная линия и подложка цвета поверхности). Справа крупное имя, затем `lead` и абзацы.
  - Подблок «Опыт Андрея»: 4 строки «картинка слева — период, заголовок, текст». На телефоне картинка сверху, порядок сохраняется.
- [ ] **Step 4: Запуск** → PASS.
- [ ] **Step 5: Коммит** `feat: about and experience`.

---

### Task 7: Направления работы

**Files:**
- Create: `resources/views/sections/directions.blade.php`
- Test: `tests/Feature/DirectionsTest.php`, `tests/e2e/directions.spec.ts`

**Interfaces:**
- Consumes: `trainingIntro()`, `formats()`, `topicGroups()`, `eventServices()`, `<x-modal name="format">`.

- [ ] **Step 1: Падающие тесты.**

```php
it('renders both directions with all items', function () {
    $c = app(\App\Content\ContentRepository::class); $r = $this->get('/');
    $r->assertSee('Обучение и спикерство')->assertSee('Организация публичных мероприятий')
      ->assertSee('Ознакомьтесь с форматами обучений')->assertSee('Ознакомьтесь с темами обучений');
    foreach ($c->topicGroups() as $g) { $r->assertSee($g['title']); foreach ($g['items'] as $i) $r->assertSee($i['title']); }
    foreach ($c->eventServices() as $s) $r->assertSee($s['title']);
    expect(substr_count($r->getContent(), 'data-format-card'))->toBe(8);
});
```

```ts
test('format card opens detail and Esc returns focus', async ({ page }) => {
  await page.goto('/#directions'); await page.getByRole('button', { name: 'Принять' }).click();
  const card = page.locator('[data-format-card]').first(); await card.click();
  await expect(page.getByRole('dialog')).toContainText('Индивидуальное обучение');
  await page.keyboard.press('Escape'); await expect(page.getByRole('dialog')).toBeHidden(); await expect(card).toBeFocused();
});
test('topic group expands and collapses with aria-expanded', async ({ page }) => {
  await page.goto('/#directions'); const h = page.getByRole('button', { name: 'Обучение продажам' });
  await h.click(); await expect(h).toHaveAttribute('aria-expanded', 'true');
  await expect(page.getByText('Базовые продажи для начинающих')).toBeVisible();
  await h.click(); await expect(h).toHaveAttribute('aria-expanded', 'false');
});
```

- [ ] **Step 2: Запуск** → FAIL.
- [ ] **Step 3: Реализовать.**
  - Форматы: панно 4×2 на десктопе, 2×4 на планшете, 1 колонка на телефоне. Карточка — `<button data-format-card>` с номером, названием и метками. Клик открывает окно с полным описанием и кнопкой КП `source="format"`.
  - Темы: аккордеон групп (`<button aria-expanded aria-controls>`), внутри сетка карточек «название + описание». Плавная высота через `x-collapse` (`@alpinejs/collapse`) — добавить зависимость.
  - Мероприятия: аккордеон из 7 пунктов, по одному открытому за раз.
  - Обе части заканчиваются спокойной кнопкой КП (`source="training"` / `source="events-org"`).
- [ ] **Step 4: Запуск** → PASS.
- [ ] **Step 5: Коммит** `feat: directions — formats, topics, events`.

---

### Task 8: Кейсы с полноэкранным просмотром и собственными адресами

**Files:**
- Create: `resources/views/sections/cases.blade.php`, `resources/views/cases/overlay.blade.php`, `resources/js/ui/overlay-route.js`
- Modify: `routes/web.php`, `PageController.php`
- Test: `tests/Feature/CasesTest.php`, `tests/e2e/cases.spec.ts`

**Interfaces:**
- Consumes: `cases()`, `findCase()`.
- Produces:
  - Маршрут `cases.show` (`/cases/{slug}`) → `PageController@case` рендерит `pages.home` с `$openCase` или отдаёт 404.
  - Модуль `overlayRoute`: `Alpine.data('overlayRoute', (homeUrl) => ...)`:
    - `openItem(slug, url)` открывает окно и делает `history.pushState`;
    - `close()` закрывает окно и делает `history.back()`, если состояние наше, иначе `replaceState(homeUrl)`;
    - `popstate` синхронизирует окно с адресом.
  - Используется и в Task 9.

- [ ] **Step 1: Падающие тесты.**

```php
it('shows case grid split by direction with feed button', function () {
    $r = $this->get('/')->assertSee('Реальная практика, подтверждённая результатами')
        ->assertSee('Посмотреть актуальные кейсы в ленте событий Андрея');
    expect(substr_count($r->getContent(), 'data-case-card'))->toBe(8);
});
it('deep link renders home with that case open', function () {
    $case = app(\App\Content\ContentRepository::class)->cases()[0];
    $this->get('/cases/'.$case['slug'])->assertOk()->assertSee('data-open-case="'.$case['slug'].'"', false)
        ->assertSee($case['request'])->assertSee($case['review']);
});
it('404s on unknown case', fn () => $this->get('/cases/nope')->assertNotFound());
```

```ts
test('case opens fullscreen, URL changes, Back closes', async ({ page }) => {
  await page.goto('/'); await page.getByRole('button', { name: 'Принять' }).click();
  await page.locator('[data-case-card]').first().click();
  await expect(page).toHaveURL(/\/cases\//); await expect(page.getByRole('dialog')).toBeVisible();
  await page.goBack(); await expect(page.getByRole('dialog')).toBeHidden(); await expect(page).toHaveURL(/\/$/);
});
test('direct case link opens overlay; Esc goes to home URL', async ({ page }) => {
  await page.goto('/'); const href = await page.locator('[data-case-card]').first().getAttribute('href');
  await page.goto(href!); await expect(page.getByRole('dialog')).toBeVisible();
  await page.keyboard.press('Escape'); await expect(page).not.toHaveURL(/\/cases\//);
});
```

- [ ] **Step 2: Запуск** → FAIL.
- [ ] **Step 3: Реализовать.**
  - Карточка — `<a href="{{ route('cases.show', $slug) }}" data-case-card>` (ссылка работает и без JS). Клик перехватывается и вызывает `openItem`.
  - Переключатель направлений «Обучение / Мероприятия» над сеткой (табы, по умолчанию «Все»).
  - Карточка: крупное название, дата, число участников.
  - Полноэкранное окно: шапка с названием, датой и участниками, затем «Запрос заказчика», «План мероприятия», «Результат», «Отзыв заказчика» (цитатой), 3 фото, кнопка КП `source="case"`.
  - Под сеткой — ссылка на `route('events.index')` с текстом из теста.
  - `<title>` и `meta description` страницы кейса берутся из кейса.
- [ ] **Step 4: Запуск** → PASS.
- [ ] **Step 5: Коммит** `feat: cases grid with fullscreen deep-linked overlay`.

---

### Task 9: Лента событий — блок на главной, страница `/events`, адреса событий

**Files:**
- Create: `resources/views/sections/events.blade.php`, `resources/views/pages/events.blade.php`, `resources/views/events/{item,overlay}.blade.php`
- Modify: `routes/web.php`, `PageController.php`
- Test: `tests/Feature/EventsTest.php`, `tests/e2e/events.spec.ts`

**Interfaces:**
- Consumes: `events(?int)`, `findEvent()`, `overlayRoute` из Task 8.
- Produces:
  - маршрут `events.index` (`/events`) — все события без пагинации (на этапе 1 событий мало; постраничность — этап 3);
  - маршрут `events.show` (`/events/{slug}`) рендерит `pages.events` с `$openEvent` или отдаёт 404.

- [ ] **Step 1: Падающие тесты.**

```php
it('home shows 6 latest events and link to all', function () {
    $r = $this->get('/')->assertSee('Открыть всю ленту');
    expect(substr_count($r->getContent(), 'data-event-item'))->toBe(min(6, count(app(\App\Content\ContentRepository::class)->events())));
});
it('events page lists all, newest first', function () {
    $e = app(\App\Content\ContentRepository::class)->events();
    $this->get('/events')->assertOk()->assertSeeInOrder([$e[0]['title'], $e[1]['title']]);
});
it('event deep link opens it; unknown 404s', function () {
    $e = app(\App\Content\ContentRepository::class)->events()[0];
    $this->get('/events/'.$e['slug'])->assertOk()->assertSee('data-open-event="'.$e['slug'].'"', false);
    $this->get('/events/nope')->assertNotFound();
});
```

  e2e: клик по событию меняет адрес на `/events/<slug>`, «Назад» закрывает окно — по образцу Task 8.
- [ ] **Step 2: Запуск** → FAIL.
- [ ] **Step 3: Реализовать.**
  - Элемент списка (по референсу watermelon blocks/blog): дата и заказчик меткой, название, 2–3 строки описания, полоска из первых 3 фото.
  - Окно события: всё описание и все 3–6 фото.
  - Дата выводится по-русски: «27 февраля 2025» через `Carbon::parse()->translatedFormat('j F Y')`.
- [ ] **Step 4: Запуск** → PASS.
- [ ] **Step 5: Коммит** `feat: events feed, events page, deep links`.

---

### Task 10: Партнёры, FAQ, галерея, регалии, контакты

**Files:**
- Create: `resources/views/sections/{partners,faq,gallery,certificates,contacts}.blade.php`, `resources/js/ui/lightbox.js`
- Test: `tests/Feature/SimpleBlocksTest.php`, `tests/e2e/gallery.spec.ts`

**Interfaces:**
- Consumes: `partners()`, `faq()`, `gallery()`, `certificates()`, `contacts()`.
- Produces: `Alpine.data('lightbox', (items) => ({ index, open(i), next(), prev(), close() }))`. Листание по кругу.

- [ ] **Step 1: Падающие тесты.**

```php
it('renders simple blocks', function () {
    $c = app(\App\Content\ContentRepository::class); $r = $this->get('/');
    $r->assertSee('Друзья и партнёры')->assertSee('Частые вопросы к Андрею')->assertSee('Галерея')
      ->assertSee('Регалии и сертификаты')->assertSee('Всё уже в ваших руках, остался последний шаг')
      ->assertSee($c->contacts()['vk'])->assertSee($c->contacts()['telegram'])->assertSee($c->contacts()['max']);
    foreach ($c->faq() as $f) $r->assertSee($f['q']);
    foreach ($c->certificates() as $cert) $r->assertSee((string) $cert['year']);
});
```

```ts
test('lightbox: arrows wrap, Esc closes', async ({ page }) => {
  await page.goto('/#gallery'); await page.getByRole('button', { name: 'Принять' }).click();
  const items = page.locator('[data-gallery-item]'); const n = await items.count();
  await items.last().click(); await page.keyboard.press('ArrowRight');
  await expect(page.getByRole('dialog')).toHaveAttribute('data-index', '0');
  await page.keyboard.press('Escape'); await expect(page.getByRole('dialog')).toBeHidden();
});
```

- [ ] **Step 2: Запуск** → FAIL.
- [ ] **Step 3: Реализовать.**
  - **Партнёры и регалии:** одинаковая сетка карточек — изображение (`object-contain` для логотипов на светлой подложке, `object-cover` для фото), подпись; у регалий ещё год.
  - **FAQ:** аккордеон.
  - **Галерея:** сетка с разной высотой рядов. Лайтбокс: стрелки, свайп (pointer events, порог 40 px), Esc, подпись, счётчик «3 / 12».
  - **Контакты:** надпись из ТЗ, крупные ссылки-плитки (телефон, VK, Telegram, MAX) и главная кнопка КП `source="contacts"`.
  - Каждый блок, кроме галереи, заканчивается спокойной кнопкой КП со своим `source`.
- [ ] **Step 4: Запуск** → PASS.
- [ ] **Step 5: Коммит** `feat: partners, faq, gallery lightbox, certificates, contacts`.

---

### Task 11: Моментальное КП

**Files:**
- Create: `resources/views/kp/{form,document}.blade.php`, `resources/js/kp/{kp-form,file-name,lead-sink,pdf}.js`, `resources/css/kp-print.css`
- Test: `tests/js/kp-file-name.test.mjs`, `tests/Feature/KpTest.php`, `tests/e2e/kp.spec.ts`

**Interfaces:**
- Consumes: стор `modals`, событие `kp-open` с `{source}`, `kp()`, `contacts()`, `about()`.
- Produces:
  - `kpFileName(company: string): string` в `file-name.js`;
  - `sendLead({company, city, source, createdAt}): Promise<void>` в `lead-sink.js`. Если нет `<meta name="kp-endpoint">`, сразу резолвится. Если есть — делает POST JSON и **никогда не реджектится** (ошибки только в консоль). Этап 3 добавит мету;
  - `downloadKp(el: HTMLElement, fileName: string): Promise<void>` в `pdf.js`: при клике лениво подгружает `html2pdf.js@0.10.1` с cdnjs, а при ошибке загрузки или генерации вызывает `window.print()`;
  - `Alpine.data('kpForm')` с полями `company`, `city`, `source`, `error`, `submitted` и методом `submit()`.

- [ ] **Step 1: Падающий юнит-тест имени файла.**

```js
test('plain', () => assert.equal(kpFileName('ООО Ромашка'), 'КП_Матузов_ООО_Ромашка.pdf'));
test('quotes and forbidden chars stripped', () => assert.equal(kpFileName('ООО «Ромашка» / "Лето":*?<>|'), 'КП_Матузов_ООО_Ромашка_Лето.pdf'));
test('emoji stripped, ё kept', () => assert.equal(kpFileName('Ёлка 🎄 Групп'), 'КП_Матузов_Ёлка_Групп.pdf'));
test('max 60 chars of company', () => assert.equal(kpFileName('А'.repeat(200)), `КП_Матузов_${'А'.repeat(60)}.pdf`));
test('fallback when nothing left', () => assert.equal(kpFileName('«»🎄'), 'КП_Матузов.pdf'));
```

  Правило очистки: оставить `\p{L}\p{N}`, пробел и дефис; пробелы схлопнуть в `_`; обрезать края и подчёркивания; ограничить 60 символами.
- [ ] **Step 2: Запуск** `node --test tests/js/` → FAIL. Реализовать `kpFileName` → PASS.
- [ ] **Step 3: Падающие feature- и e2e-тесты.**

```php
it('renders KP form with exact heading and no personal fields', function () {
    $html = $this->get('/')->assertSee('Прочитав коммерческое предложение — вы уже на полпути к результатам')->getContent();
    expect($html)->not->toMatch('/type="(tel|email)"/')->not->toContain('name="phone"');
});
it('renders KP document template with prices', fn () =>
    $this->get('/')->assertSee('data-kp-document', false)->assertSee(app(\App\Content\ContentRepository::class)->kp()['prices'][0]['label']));
```

```ts
test.beforeEach(async ({ page }) => { await page.goto('/'); await page.getByRole('button', { name: 'Принять' }).click(); });
test('empty company shows error, no KP', async ({ page }) => {
  await page.locator('[data-kp-source="hero"]').click();
  await page.getByRole('button', { name: 'Сформировать КП' }).click();
  await expect(page.getByText('Укажите название компании')).toBeVisible();
  await expect(page.locator('[data-kp-document]')).toBeHidden();
});
test('company with quotes renders escaped and personalised', async ({ page }) => {
  await page.locator('[data-kp-source="cases"]').first().click();
  await page.getByLabel('Название компании').fill('ООО «Ромашка» <b>x</b>'); await page.getByLabel('Город').fill('Казань');
  await page.getByRole('button', { name: 'Сформировать КП' }).click();
  const doc = page.locator('[data-kp-document]');
  await expect(doc).toContainText('ООО «Ромашка» <b>x</b>'); await expect(doc).toContainText('Казань');
  await expect(doc.locator('b', { hasText: 'x' })).toHaveCount(0);
});
test('PDF downloads with sanitised name', async ({ page }) => {
  await page.locator('[data-kp-source="floating"]').click();
  await page.getByLabel('Название компании').fill('ООО Ромашка'); await page.getByRole('button', { name: 'Сформировать КП' }).click();
  const dl = page.waitForEvent('download'); await page.getByRole('button', { name: 'Скачать PDF' }).first().click();
  expect((await dl).suggestedFilename()).toBe('КП_Матузов_ООО_Ромашка.pdf');
});
test('falls back to print when html2pdf is blocked', async ({ page }) => {
  await page.route('**/html2pdf*', r => r.abort());
  await page.evaluate(() => { (window as any).__printed = false; window.print = () => { (window as any).__printed = true; }; });
  await page.locator('[data-kp-source="hero"]').click();
  await page.getByLabel('Название компании').fill('Тест'); await page.getByRole('button', { name: 'Сформировать КП' }).click();
  await page.getByRole('button', { name: 'Скачать PDF' }).first().click();
  await expect.poll(() => page.evaluate(() => (window as any).__printed)).toBe(true);
});
test('KP from inside an open case replaces the case overlay', async ({ page }) => {
  await page.locator('[data-case-card]').first().click();
  await page.getByRole('dialog').locator('[data-kp-source="case"]').click();
  await expect(page.getByRole('dialog')).toHaveCount(1);
  await expect(page.getByText('Прочитав коммерческое предложение')).toBeVisible();
});
```

- [ ] **Step 4: Запуск** → FAIL.
- [ ] **Step 5: Реализовать форму.**
  - Окно `<x-modal name="kp-form">` с надписью из Global Constraints.
  - Поля «Название компании» (`autocomplete="organization"`, обязательно) и «Город» (`autocomplete="address-level2"`). Под формой: «Телефон не нужен».
  - Кнопка «Сформировать КП». Текст ошибки: «Укажите название компании — без него не получится адресовать КП».
  - При `kp-open` вызываются `closeAll()`, затем `open('kp-form')`, и фокус ставится в поле компании.
- [ ] **Step 6: Реализовать документ КП.**
  - Полноэкранное окно `<x-modal name="kp-document" fullscreen>`. Сверху панель с кнопками «Скачать PDF» и «Закрыть», внизу ещё одна «Скачать PDF».
  - Внутри — лист `[data-kp-document]` формата A4, **светлый** (белая бумага, акценты палитры), разделы по черновику:
    - шапка: имя, «корпоративный тренер-консультант · Санкт-Петербург», контакты;
    - «Коммерческое предложение для компании „<компания>“ · город <город> · <дата>»;
    - «Почему вы дочитаете это КП до конца»;
    - 01 Кто проводит, 02 Акцент работы, 03 Формат работы, 04 Стоимость обучения (таблица) + условия (самозанятость, ФЗ № 422), 05 Темы, 06 Достижения;
    - финальная фраза.
  - Подстановка только через `x-text` — никакого `innerHTML`.
  - `kp-print.css`: `@media print` печатает только лист, `break-inside: avoid` для блоков. Для html2pdf: `pagebreak: { mode: ['css', 'avoid-all'] }`.
  - При отправке формы `sendLead()` вызывается без ожидания результата: КП показывается сразу.
- [ ] **Step 7: Запуск** `./vendor/bin/pest && node --test tests/js/ && npx playwright test tests/e2e/kp.spec.ts` → PASS.
- [ ] **Step 8: Коммит** `feat: instant KP — form, personalised document, PDF with print fallback`.

---

### Task 12: Статичная выгрузка и GitHub Pages

**Files:**
- Create: `config/export.php` (publish), `scripts/check-export.mjs`, `.github/workflows/pages.yml`, `resources/views/errors/404.blade.php`, `public/.nojekyll`
- Modify: `routes/web.php` (маршрут `/404.html` только для выгрузки), `package.json` (скрипт `check:export`)
- Test: `scripts/check-export.mjs`

**Interfaces:**
- Consumes: все маршруты.
- Produces:
  - `php artisan export` → `dist/` со страницами `index.html`, `events/index.html`, `cases/<slug>/index.html`, `events/<slug>/index.html`, `404.html` и всей папкой `public/`;
  - `node scripts/check-export.mjs dist <basePath>` → код выхода 0 или 1 со списком нарушений.

- [ ] **Step 1: Написать проверку выгрузки.** `scripts/check-export.mjs` проверяет:
  - наличие страниц из Produces (по одному кейсу и событию на каждый slug из `dist/cases/*` и `dist/events/*`, всего 8 кейсов);
  - что во всех `*.html` нет `href="/` или `src="/` и `srcset="/`, не начинающихся с `<basePath>/`;
  - что нет ссылок на `http://127.0.0.1`, `localhost` и `APP_URL` из `.env`;
  - что каждый локальный `href`/`src` указывает на существующий файл в `dist/`.
- [ ] **Step 2: Установить и настроить выгрузку.**
  - `composer require spatie/laravel-export`, `php artisan vendor:publish --tag=export-config`.
  - Маршрут `/sitemap.xml`: главная, `/events`, все кейсы и события. `/robots.txt` со ссылкой на sitemap. На Pages-превью (`SITE_PREVIEW=true`) — `Disallow: /`, чтобы черновик не попал в Яндекс. Стандартный `public/robots.txt` удалить, иначе он перекроет маршрут.
  - В конфиге: `crawl => true`, `paths => ['/404.html', '/sitemap.xml', '/robots.txt']`, `include_files => ['public' => '']`, `exclude_file_patterns` для `index.php`, `.htaccess`, `hot`, `mix-manifest.json`. В `disks` — `dist`.
  - Сборка: `APP_URL=https://<owner>.github.io/<repo> ASSET_URL=$APP_URL npm run build && php artisan export`.
- [ ] **Step 3: Запуск локально** с `APP_URL=http://localhost/repo-test`: `php artisan export && node scripts/check-export.mjs dist /repo-test`. Сначала FAIL — исправить все найденные пути от корня в шаблонах, затем PASS.
- [ ] **Step 4: Workflow `pages.yml`.**
  - Запуск на push в `main` и вручную.
  - Шаги: `shivammathur/setup-php` (8.3, extensions intl, zip), `actions/setup-node` (24), `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run images`.
  - `APP_URL="https://${{ github.repository_owner }}.github.io/${{ github.event.repository.name }}"`, `ASSET_URL` = то же значение, `SITE_PREVIEW=true`, `APP_KEY` генерируется в шаге (`php artisan key:generate`).
  - Затем `npm run build`, `php artisan export`, `node scripts/check-export.mjs dist /${{ github.event.repository.name }}`.
  - `actions/upload-pages-artifact` (path `dist`), `actions/deploy-pages`. Права: `pages: write`, `id-token: write`.
- [ ] **Step 5: Коммит** `ci: static export and GitHub Pages deploy`. Публикация репозитория на GitHub — после того как заказчик даст аккаунт и название (спецификация §11). До этого Task 12 считается сделанным при PASS локальной проверки.

---

### Task 13: Финальная проверка качества

**Files:**
- Create: `tests/e2e/a11y.spec.ts`, `tests/e2e/responsive.spec.ts`
- Modify: всё, что найдут проверки

- [ ] **Step 1: Тесты.**
  - **Доступность:** axe на `/`, `/events`, `/cases/<первый>` и в открытом КП-документе — 0 нарушений уровня `serious` и `critical`.
  - **Адаптивность:** на ширинах 375, 768, 1280, 1440:
    - `scrollWidth <= innerWidth`;
    - плавающая КП-кнопка не пересекается (`boundingBox`) с кнопкой «Сформировать КП» при открытой форме;
    - скриншот каждой секции сохраняется в `test-results/`.
- [ ] **Step 2: Запуск** `npx playwright test` → исправить до PASS.
- [ ] **Step 3: Аудит дизайна.**
  - Прогнать `impeccable` (режимы `audit` и `critique`) и чекер `design-taste-frontend` на всех скриншотах.
  - Исправить всё, что они отмечают как шаблонность, неровные края, слабую иерархию или навязчивые кнопки КП.
- [ ] **Step 4: Lighthouse.** Мобильный профиль по `dist/` через `npx serve dist`. Цель: Performance ≥ 85, Accessibility ≥ 95, SEO ≥ 90. Записать цифры в `DESIGN.md`.
- [ ] **Step 5: Полный прогон.** `./vendor/bin/pest && node --test tests/js/ && npx playwright test` → всё PASS.
- [ ] **Step 6: Коммит** `chore: a11y, responsive and design audit fixes`.
