<?php

namespace App\Content;

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
use App\Models\Setting;
use App\Models\Topic;
use App\Models\TopicGroup;
use App\Site\PublishQueue;
use App\Support\ImageManifest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Разовый перенос статичного наполнения (StaticContentRepository) в базу.
 * Одна транзакция; каждый уникальный исходник фото нарезается один раз,
 * остальные строки получают собственные копии оригинала и вариантов.
 */
class ContentImporter
{
    /** @var array<string, Media> первая обработанная строка по исходнику */
    private array $processed = [];

    /** @var list<string> оригиналы на диске local, записанные этим импортом */
    private array $originals = [];

    /** @var list<string> key вариантов, записанные этим импортом */
    private array $keys = [];

    public function __construct(private StaticContentRepository $source) {}

    public function importIfEmpty(): bool
    {
        // Импорт долгий (сотни копий файлов), а хостинг может ограничивать время выполнения.
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        ignore_user_abort(true);

        if (CaseStudy::query()->exists()) {
            @unlink($this->journalPath()); // файлы журнала принадлежат уже импортированным данным

            return false;
        }
        $this->cleanupPreviousAttempt();

        $this->processed = $this->originals = $this->keys = [];
        // Публикацию просим один раз в конце, а не на каждую строку.
        $queue = app(PublishQueue::class);
        app()->instance(PublishQueue::class, new class implements PublishQueue
        {
            public function request(): void {}
        });

        try {
            DB::transaction(fn () => $this->import());
        } catch (Throwable $e) {
            $this->cleanupFiles();
            throw $e;
        } finally {
            app()->instance(PublishQueue::class, $queue);
        }

        @unlink($this->journalPath());
        ImageManifest::flush();
        $queue->request();

        return true;
    }

    private function import(): void
    {
        $s = $this->source;

        $hero = $s->hero();
        Setting::put('hero', collect($hero)->only(['title', 'subtitle', 'note', 'subject_side'])->all());
        $this->photo(null, 'hero', $hero['image'], 1, $hero['focal']['x'], $hero['focal']['y']);
        if ($hero['image_mobile']) {
            $this->photo(null, 'hero_mobile', $hero['image_mobile'], 1);
        }
        $about = $s->about();
        Setting::put('about', collect($about)->only(['name', 'lead', 'paragraphs', 'city'])->all());
        $this->photo(null, 'about', $about['photo'], 1);
        Setting::put('training_intro', $s->trainingIntro());
        Setting::put('contacts', $s->contacts());
        Setting::put('kp', $s->kp());

        foreach ($s->principles() as $i => $text) {
            Principle::create(['text' => $text, 'sort' => $i + 1]);
        }
        foreach ($s->experience() as $i => $row) {
            $stage = ExperienceStage::create(['title' => $row['title'], 'period' => $row['period'], 'text' => $row['text'], 'sort' => $i + 1]);
            $this->photo($stage, 'image', $row['image'], 1);
        }
        foreach ($s->formats() as $i => $row) {
            Format::create(['title' => $row['title'], 'tags' => $row['tags'], 'description' => $row['description'], 'sort' => $i + 1]);
        }
        $n = 0;
        foreach ($s->topicGroups() as $i => $group) {
            $g = TopicGroup::create(['title' => $group['title'], 'sort' => $i + 1]);
            foreach ($group['items'] as $item) {
                Topic::create(['topic_group_id' => $g->id, 'title' => $item['title'], 'text' => $item['text'], 'sort' => ++$n]);
            }
        }
        foreach ($s->eventServices() as $i => $row) {
            EventService::create(['title' => $row['title'], 'text' => $row['text'], 'sort' => $i + 1]);
        }
        foreach ($s->cases() as $i => $row) {
            $case = CaseStudy::create([
                'slug' => $row['slug'], 'direction' => $row['direction'], 'title' => $row['title'],
                'client' => $row['client'], 'date_label' => $row['date'], 'people' => $row['people'],
                'request' => $row['request'], 'plan' => $this->plan($row['plan']),
                'result' => $row['result'], 'review' => $row['review'], 'demo' => $row['demo'], 'sort' => $i + 1,
            ]);
            $this->photo($case, 'cover', $row['photos'][0], 1);
            foreach ($row['photos'] as $k => $image) {
                $this->photo($case, 'photos', $image, $k + 1);
            }
        }
        foreach ($s->events() as $i => $row) {
            $event = Event::create([
                'slug' => $row['slug'], 'title' => $row['title'], 'date' => date('Y-m-d', strtotime($row['date'])),
                'client' => $row['client'], 'description' => $row['description'], 'demo' => $row['demo'], 'sort' => $i + 1,
            ]);
            foreach ($row['photos'] as $k => $image) {
                $this->photo($event, 'photos', $image, $k + 1);
            }
        }
        foreach ($s->partners() as $i => $row) {
            $partner = Partner::create(['name' => $row['name'], 'short' => $row['short'], 'description' => $row['description'], 'url' => $row['url'], 'sort' => $i + 1]);
            if ($row['logo']) {
                $this->photo($partner, 'logo', $row['logo'], 1);
            }
        }
        foreach ($s->certificates() as $i => $row) {
            $c = Certificate::create(['title' => $row['title'], 'description' => $row['description'], 'year' => $row['year'], 'sort' => $i + 1]);
            $this->photo($c, 'image', $row['image'], 1);
        }
        foreach ($s->gallery() as $i => $row) {
            $item = GalleryItem::create(['caption' => $row['caption'], 'sort' => $i + 1]);
            $this->photo($item, 'image', $row['image'], 1);
        }
        foreach ($s->faq() as $i => $row) {
            FaqItem::create(['question' => $row['q'], 'answer' => $row['a'], 'sort' => $i + 1]);
        }
    }

    /** @return list<string> */
    private function plan(string $plan): array
    {
        return collect(explode('→', $plan))
            ->map(fn ($step) => trim($step))
            ->filter()
            ->map(fn ($step) => mb_strtoupper(mb_substr($step, 0, 1)).mb_substr($step, 1))
            ->values()
            ->all();
    }

    /** Фото владельца (коллекция $name) либо одиночное фото настроек (slot $name, без владельца). */
    private function photo(?Model $owner, string $name, string $image, int $sort, int $fx = 50, int $fy = 50): void
    {
        $attrs = $owner
            ? ['owner_type' => $owner->getMorphClass(), 'owner_id' => $owner->getKey(), 'collection' => $name]
            : ['collection' => 'default', 'slot' => $name];
        $attrs += ['focal_x' => $fx, 'focal_y' => $fy, 'sort' => $sort];

        $file = glob(resource_path("images/{$image}.*"))[0] ?? throw new RuntimeException("Нет исходника фото: {$image}");
        $first = $this->processed[$image] ?? null;

        $disk = Storage::disk('local');
        $path = 'media-originals/'.bin2hex(random_bytes(8)).'-'.basename($file);
        $first ? $disk->copy($first->original_path, $path) : $disk->put($path, file_get_contents($file));
        $this->record('o', $path);
        $this->originals[] = $path;

        if (! $first) {
            $m = Media::create($attrs + ['original_path' => $path]);
            $this->processed[$image] = $m->refresh();
            $this->record('k', $this->processed[$image]->key);
            $this->keys[] = $this->processed[$image]->key;

            return;
        }

        // Без событий модели: нарезку не запускаем, варианты копируем с первой строки.
        $m = Media::withoutEvents(function () use ($attrs, $path, $first) {
            $m = Media::create($attrs + ['original_path' => $path]);
            $key = 'media/'.$m->id.'-'.bin2hex(random_bytes(4));
            $this->record('k', $key);
            $m->forceFill([
                'key' => $key,
                'widths' => $first->widths, 'formats' => $first->formats,
                'width' => $first->width, 'height' => $first->height,
            ])->save();

            return $m;
        });
        $this->keys[] = $m->key;
        foreach ($first->widths as $w) {
            foreach ($first->formats as $f) {
                copy(public_path("{$first->key}-{$w}.{$f}"), public_path("{$m->key}-{$w}.{$f}"));
            }
        }
    }

    private function cleanupFiles(): void
    {
        $this->removeFiles($this->originals, $this->keys);
        @unlink($this->journalPath());
    }

    /** Журнал записанных файлов: переживает фатальную ошибку (таймаут), в которой catch не сработает. */
    private function journalPath(): string
    {
        return storage_path('framework/content-import.journal');
    }

    private function record(string $type, string $value): void
    {
        $dir = dirname($this->journalPath());
        is_dir($dir) || mkdir($dir, 0755, true);
        file_put_contents($this->journalPath(), $type.':'.$value."
", FILE_APPEND | LOCK_EX);
    }

    /** Убирает файлы прерванной попытки (база при этом пуста — транзакция откатилась). */
    private function cleanupPreviousAttempt(): void
    {
        $lines = is_file($this->journalPath()) ? file($this->journalPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $pick = fn (string $t) => array_map(fn ($l) => substr($l, 2), array_filter($lines, fn ($l) => str_starts_with($l, $t.':')));
        $this->removeFiles($pick('o'), $pick('k'));
        @unlink($this->journalPath());
    }

    private function removeFiles(array $originals, array $keys): void
    {
        foreach ($originals as $path) {
            Storage::disk('local')->delete($path);
        }
        foreach ($keys as $key) {
            foreach (glob(public_path($key).'-*') ?: [] as $file) {
                @unlink($file);
            }
        }
    }
}
