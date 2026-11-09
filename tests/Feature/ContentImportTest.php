<?php

use App\Content\ContentImporter;
use App\Content\StaticContentRepository;
use App\Media\MediaProcessor;
use App\Models\CaseStudy;
use App\Models\Event;
use App\Models\Media;
use App\Models\Principle;
use App\Models\Setting;
use App\Models\Topic;
use App\Site\PublishQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->publicDir = sys_get_temp_dir().'/import-test-'.bin2hex(random_bytes(4));
    mkdir($this->publicDir);
    $this->originalPublic = public_path();
    app()->usePublicPath($this->publicDir);
});

afterEach(function () {
    app()->usePublicPath($this->originalPublic);
    File::deleteDirectory($this->publicDir);
});

it('imports all static content once', function () {
    $s = app(StaticContentRepository::class);
    expect(app(ContentImporter::class)->importIfEmpty())->toBeTrue();
    expect(CaseStudy::count())->toBe(count($s->cases()))
        ->and(Event::count())->toBe(count($s->events()))
        ->and(Topic::count())->toBe(collect($s->topicGroups())->sum(fn ($g) => count($g['items'])))
        ->and(Principle::count())->toBe(8)
        ->and(Setting::get('kp')['prices'])->toBe($s->kp()['prices'])
        ->and(CaseStudy::first()->plan[0])->toBe('Анализ 120 записей реальных звонков')
        ->and(CaseStudy::first()->media('photos')->count())->toBe(3);
    expect(app(ContentImporter::class)->importIfEmpty())->toBeFalse();
});

it('processes each unique source once and gives every row its own files', function () {
    $calls = 0;
    $real = app(MediaProcessor::class);
    app()->instance(MediaProcessor::class, new class($real, $calls) extends MediaProcessor
    {
        public function __construct(private MediaProcessor $real, private int &$calls) {}

        public function process(Media $m): void
        {
            $this->calls++;
            $this->real->process($m);
        }

        public function deleteVariants(Media $m): void
        {
            $this->real->deleteVariants($m);
        }
    });

    app(ContentImporter::class)->importIfEmpty();

    expect($calls)->toBe(3);
    $all = Media::all();
    expect($all->count())->toBeGreaterThan(100)
        ->and($all->pluck('key')->filter()->unique()->count())->toBe($all->count())
        ->and($all->pluck('original_path')->unique()->count())->toBe($all->count());
    foreach ($all->random(5) as $m) {
        expect(Storage::disk('local')->exists($m->original_path))->toBeTrue()
            ->and($m->widths)->not->toBeEmpty()->and($m->width)->toBeGreaterThan(0)
            ->and(file_exists(public_path("{$m->key}-{$m->widths[0]}.webp")))->toBeTrue();
    }

    $hero = Media::where('slot', 'hero')->first();
    expect($hero->focal_x)->toBe(24)->and($hero->focal_y)->toBe(30)
        ->and(Media::where('slot', 'hero_mobile')->exists())->toBeFalse()
        ->and(Media::where('slot', 'about')->exists())->toBeTrue()
        ->and(CaseStudy::first()->media('cover')->count())->toBe(1);

    // удаление одной строки не трогает чужие файлы
    $a = $all[0];
    $b = $all->first(fn ($m) => $m->id !== $a->id && $m->original_path !== $a->original_path);
    $a->delete();
    expect(Storage::disk('local')->exists($b->original_path))->toBeTrue()
        ->and(file_exists(public_path("{$b->key}-{$b->widths[0]}.webp")))->toBeTrue();
});

it('requests publishing once', function () {
    $queue = new class implements PublishQueue
    {
        public int $n = 0;

        public function request(): void
        {
            $this->n++;
        }
    };
    app()->instance(PublishQueue::class, $queue);
    app(ContentImporter::class)->importIfEmpty();
    expect($queue->n)->toBe(1);
});

it('rolls back rows and files when the import fails', function () {
    $broken = new class extends StaticContentRepository
    {
        public function faq(): array
        {
            throw new RuntimeException('boom');
        }
    };
    expect(fn () => (new ContentImporter($broken))->importIfEmpty())->toThrow(RuntimeException::class);
    expect(CaseStudy::count())->toBe(0)->and(Media::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(File::allFiles($this->publicDir))->toBe([]);
});
