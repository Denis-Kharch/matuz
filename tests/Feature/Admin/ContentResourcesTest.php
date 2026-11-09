<?php

use App\Filament\Resources\CaseStudies\CaseStudyResource;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Filament\Resources\EventServices\EventServiceResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\ExperienceStages\ExperienceStageResource;
use App\Filament\Resources\FaqItems\FaqItemResource;
use App\Filament\Resources\Formats\FormatResource;
use App\Filament\Resources\GalleryItems\GalleryItemResource;
use App\Filament\Resources\GalleryItems\Pages\ListGalleryItems;
use App\Filament\Resources\Partners\PartnerResource;
use App\Filament\Resources\Principles\PrincipleResource;
use App\Filament\Resources\TopicGroups\TopicGroupResource;
use App\Models\CaseStudy;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventService;
use App\Models\ExperienceStage;
use App\Models\FaqItem;
use App\Models\Format;
use App\Models\GalleryItem;
use App\Models\Media;
use App\Models\Partner;
use App\Models\Principle;
use App\Models\Topic;
use App\Models\TopicGroup;
use App\Models\User;
use App\Site\PublishQueue;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->publicDir = sys_get_temp_dir().'/content-res-test-'.bin2hex(random_bytes(4));
    mkdir($this->publicDir);
    $this->originalPublic = public_path();
    app()->usePublicPath($this->publicDir);
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
});

afterEach(function () {
    app()->usePublicPath($this->originalPublic);
    File::deleteDirectory($this->publicDir);
});

/** Счётчик запросов публикации (вместо NullPublishQueue). */
function publishSpy(): object
{
    $spy = new class implements PublishQueue
    {
        public int $requests = 0;

        public function request(): void
        {
            $this->requests++;
        }
    };
    app()->instance(PublishQueue::class, $spy);

    return $spy;
}

dataset('resources', [
    'cases' => [CaseStudyResource::class, CaseStudy::class, [
        'direction' => 'training', 'title' => 'Первый кейс', 'slug' => 'pervyy-keys', 'client' => 'Клиент',
        'date_label' => 'март 2026', 'people' => '10 человек', 'request' => '<p>Запрос</p>',
        'plan' => [['step' => 'Анализ'], ['step' => 'Тренинг']], 'result' => '<p>Результат</p>', 'review' => '<p>Отзыв</p>', 'demo' => true,
    ], 'client', 'Другой клиент'],
    'events' => [EventResource::class, Event::class, [
        'title' => 'Первое событие', 'slug' => 'pervoe-sobytie', 'date' => '2026-03-01', 'client' => 'Заказчик',
        'description' => '<p>Описание</p>', 'demo' => false,
    ], 'client', 'Другой заказчик'],
    'partners' => [PartnerResource::class, Partner::class, [
        'name' => 'Партнёр', 'short' => 'П', 'description' => 'Описание', 'url' => 'https://example.com',
    ], 'name', 'Партнёр 2'],
    'certificates' => [CertificateResource::class, Certificate::class, [
        'title' => 'Диплом', 'description' => 'Описание', 'year' => '2020',
    ], 'title', 'Диплом 2'],
    'gallery' => [GalleryItemResource::class, GalleryItem::class, ['caption' => 'Подпись'], 'caption', 'Подпись 2'],
    'faq' => [FaqItemResource::class, FaqItem::class, [
        'question' => 'Вопрос?', 'answer' => '<p>Ответ</p>',
    ], 'question', 'Вопрос 2?'],
    'formats' => [FormatResource::class, Format::class, [
        'title' => 'Формат', 'tags' => ['онлайн', 'группа'], 'description' => 'Описание',
    ], 'title', 'Формат 2'],
    'topics' => [TopicGroupResource::class, TopicGroup::class, [
        'title' => 'Группа', 'topics' => [['title' => 'Тема', 'text' => 'Текст']],
    ], 'title', 'Группа 2'],
    'services' => [EventServiceResource::class, EventService::class, [
        'title' => 'Услуга', 'text' => 'Текст',
    ], 'title', 'Услуга 2'],
    'principles' => [PrincipleResource::class, Principle::class, ['text' => 'Принцип'], 'text', 'Принцип 2'],
    'stages' => [ExperienceStageResource::class, ExperienceStage::class, [
        'period' => '2010–2015', 'title' => 'Этап', 'text' => 'Текст',
    ], 'title', 'Этап 2'],
]);

it('creates, edits, hides, reorders and deletes', function (string $resource, string $model, array $data, string $editKey, string $editValue) {
    $pages = $resource::getPages();

    // Создание: запись видима, публикация запрошена
    $spy = publishSpy();
    Livewire::test($pages['create']->getPage())
        ->fillForm($data)
        ->call('create')
        ->assertHasNoFormErrors();
    expect($model::count())->toBe(1)
        ->and($spy->requests)->toBeGreaterThan(0);
    $record = $model::first();
    expect($record->is_visible)->toBeTrue();
    $plain = collect($data)->except(['topics', 'plan', 'tags', 'request', 'result', 'review', 'description', 'answer', 'date']);
    foreach ($plain as $key => $value) {
        expect($record->$key)->toEqual($value);
    }

    // Изменение
    $spy = publishSpy();
    Livewire::test($pages['edit']->getPage(), ['record' => $record->getRouteKey()])
        ->fillForm([$editKey => $editValue])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($record->fresh()->$editKey)->toBe($editValue)
        ->and($spy->requests)->toBeGreaterThan(0);

    // Список: переключатель «показывать на сайте» прямо в таблице
    $list = Livewire::test($pages['index']->getPage())->assertCanSeeTableRecords([$record->fresh()]);
    $spy = publishSpy();
    $list->call('updateTableColumnState', 'is_visible', $record->getKey(), false);
    expect($record->fresh()->is_visible)->toBeFalse()
        ->and($spy->requests)->toBeGreaterThan(0);

    // Порядок: вторую запись перетащили вперёд; публикация запрошена ровно один раз
    $replica = $record->replicate();
    $replica->sort = 0;
    if (isset($data['slug'])) {
        $replica->slug = $data['slug'].'-2';
    }
    $replica->save();
    $ids = [$replica->getKey(), $record->getKey()];
    $spy = publishSpy();
    Livewire::test($pages['index']->getPage())->call('reorderTable', $ids);
    expect($model::orderBy('sort')->first()->getKey())->toBe($replica->getKey())
        ->and($spy->requests)->toBe(1);

    // Удаление
    $spy = publishSpy();
    Livewire::test($pages['index']->getPage())->callTableAction('delete', $record);
    expect($model::find($record->getKey()))->toBeNull()
        ->and($spy->requests)->toBeGreaterThan(0);
})->with('resources');

it('saves the case plan as a list of strings and the topic cards as children', function () {
    Livewire::test(CaseStudyResource::getPages()['create']->getPage())
        ->fillForm([
            'direction' => 'events', 'title' => 'План', 'slug' => 'plan', 'client' => 'К', 'date_label' => '2026',
            'people' => '5', 'request' => '<p>з</p>', 'plan' => [['step' => 'Шаг 1'], ['step' => 'Шаг 2']], 'result' => '<p>р</p>', 'review' => '<p>о</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
    $case = CaseStudy::first();
    expect($case->plan)->toBe(['Шаг 1', 'Шаг 2']);

    // Открытие на редактирование: строки плана подгружаются и сохраняются обратно списком строк
    $edit = Livewire::test(CaseStudyResource::getPages()['edit']->getPage(), ['record' => $case->getRouteKey()]);
    expect(collect($edit->get('data.plan'))->pluck('step')->values()->all())->toBe(['Шаг 1', 'Шаг 2']);
    $edit
        ->fillForm(['client' => 'Другой'])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($case->fresh()->plan)->toBe(['Шаг 1', 'Шаг 2']);

    Livewire::test(TopicGroupResource::getPages()['create']->getPage())
        ->fillForm(['title' => 'Г', 'topics' => [['title' => 'Т1', 'text' => 'а'], ['title' => 'Т2', 'text' => 'б']]])
        ->call('create')
        ->assertHasNoFormErrors();
    expect(Topic::orderBy('sort')->pluck('title')->all())->toBe(['Т1', 'Т2']);
});

it('case slug is unique and generated from title', function () {
    $create = CaseStudyResource::getPages()['create']->getPage();
    $base = [
        'direction' => 'training', 'client' => 'К', 'date_label' => '2026', 'people' => '5',
        'request' => '<p>з</p>', 'plan' => [['step' => 'Шаг']], 'result' => '<p>р</p>', 'review' => '<p>о</p>',
    ];

    $lw = Livewire::test($create)->fillForm([...$base, 'title' => 'Отработка возражений']);
    expect($lw->get('data.slug'))->toBe('otrabotka-vozrazheniy');
    $lw->call('create')->assertHasNoFormErrors();
    expect(CaseStudy::where('slug', 'otrabotka-vozrazheniy')->exists())->toBeTrue();

    Livewire::test($create)
        ->fillForm([...$base, 'title' => 'Отработка возражений'])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
    expect(CaseStudy::count())->toBe(1);

    // Редактирование той же записи не считается дублем
    Livewire::test(CaseStudyResource::getPages()['edit']->getPage(), ['record' => CaseStudy::first()->getRouteKey()])
        ->fillForm(['client' => 'Новый'])
        ->call('save')
        ->assertHasNoFormErrors();
});

it('view on site points to the right page', function () {
    $case = CaseStudy::factory()->create(['slug' => 'moy-keys']);
    Livewire::test(CaseStudyResource::getPages()['index']->getPage())
        ->assertTableActionHasUrl('view-on-site', route('cases.show', 'moy-keys'), $case);

    $event = Event::create(['slug' => 'sob', 'title' => 'С', 'date' => '2026-01-01', 'client' => 'К', 'description' => 'о']);
    Livewire::test(EventResource::getPages()['index']->getPage())
        ->assertTableActionHasUrl('view-on-site', route('events.show', 'sob'), $event);

    $faq = FaqItem::create(['question' => 'в', 'answer' => 'о']);
    Livewire::test(FaqItemResource::getPages()['index']->getPage())
        ->assertTableActionHasUrl('view-on-site', url('/').'#faq', $faq);
});

it('bulk-creates gallery items from several uploaded photos', function () {
    $spy = publishSpy();
    Livewire::test(ListGalleryItems::class)
        ->callAction('bulkUpload', ['files' => [
            UploadedFile::fake()->image('a.jpg', 1600, 1200),
            UploadedFile::fake()->image('b.jpg', 1600, 1200),
        ]])
        ->assertHasNoActionErrors();
    expect(GalleryItem::count())->toBe(2)
        ->and(Media::where('collection', 'image')->where('owner_type', (new GalleryItem)->getMorphClass())->count())->toBe(2)
        ->and($spy->requests)->toBeGreaterThan(0);
});

it('limits bulk gallery upload to 30 files', function () {
    $files = collect(range(1, 31))->map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg", 100, 100))->all();
    Livewire::test(ListGalleryItems::class)
        ->callAction('bulkUpload', ['files' => $files])
        ->assertHasActionErrors(['files']);
    expect(GalleryItem::count())->toBe(0);
});

it('keeps database transactions off in the admin panel', function () {
    // ImageField фиксирует каждое фото своей транзакцией (файлы вариантов заменяются сразу).
    // Если включить транзакции панели, откат при сбое оставил бы строки Media без файлов.
    expect(Filament::getPanel('admin')->hasDatabaseTransactions())->toBeFalse();
});

it('groups resources into content and page navigation', function () {
    expect(CaseStudyResource::getNavigationGroup())->toBe('Контент')
        ->and(PrincipleResource::getNavigationGroup())->toBe('Страница')
        ->and(ExperienceStageResource::getNavigationGroup())->toBe('Страница');
});

it('renders list, create and edit pages', function (string $resource, string $model, array $data) {
    $this->get($resource::getUrl('index'))->assertSuccessful();
    $this->get($resource::getUrl('create'))->assertSuccessful();

    $record = $model === CaseStudy::class
        ? CaseStudy::factory()->create()
        : $model::query()->create(collect($data)->except(['topics'])->all());
    $this->get($resource::getUrl('edit', ['record' => $record]))->assertSuccessful();
})->with('resources');
