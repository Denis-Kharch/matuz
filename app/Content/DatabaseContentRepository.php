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
use App\Models\TopicGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Наполнение из базы (админка): те же формы массивов, что у StaticContentRepository,
 * только видимые элементы по порядку sort. Картинки — ключи `media/…` для ImageManifest.
 */
class DatabaseContentRepository implements ContentRepository
{
    /** Ключ без записи в media: <x-picture> для него ничего не выводит. */
    private const NO_IMAGE = 'media/none';

    public function hero(): array
    {
        $s = Setting::get('hero', []);
        $image = $this->slot('hero');
        $mobile = $this->slot('hero_mobile');

        return [
            'title' => $s['title'] ?? '',
            'subtitle' => $s['subtitle'] ?? '',
            'note' => $s['note'] ?? '',
            'image' => $image?->key ?? self::NO_IMAGE,
            'image_mobile' => $mobile?->key,
            'focal' => ['x' => $image?->focal_x ?? 50, 'y' => $image?->focal_y ?? 50],
            'subject_side' => $s['subject_side'] ?? 'left',
        ];
    }

    public function principles(): array
    {
        return Principle::visible()->ordered()->pluck('text')->all();
    }

    public function about(): array
    {
        $s = Setting::get('about', []);

        return [
            'name' => $s['name'] ?? '',
            'lead' => $s['lead'] ?? '',
            'paragraphs' => array_values($s['paragraphs'] ?? []),
            'city' => $s['city'] ?? '',
            'photo' => $this->slot('about')?->key ?? self::NO_IMAGE,
        ];
    }

    public function experience(): array
    {
        $rows = ExperienceStage::visible()->ordered()->get();
        $images = $this->media(ExperienceStage::class, 'image');

        return $rows->map(fn ($r) => [
            'title' => $r->title,
            'period' => $r->period,
            'text' => $r->text,
            'image' => $this->keys($images, $r)[0] ?? self::NO_IMAGE,
        ])->all();
    }

    public function trainingIntro(): string
    {
        return (string) Setting::get('training_intro', '');
    }

    public function formats(): array
    {
        return Format::visible()->ordered()->get()->values()->map(fn ($r, $i) => [
            'n' => str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
            'title' => $r->title,
            'tags' => array_values($r->tags ?? []),
            'description' => $r->description,
        ])->all();
    }

    public function topicGroups(): array
    {
        return TopicGroup::visible()->ordered()->with(['topics' => fn ($q) => $q->where('is_visible', true)])->get()
            ->map(fn ($g) => [
                'title' => $g->title,
                'items' => $g->topics->map(fn ($t) => ['title' => $t->title, 'text' => $t->text])->all(),
            ])->all();
    }

    public function eventServices(): array
    {
        return EventService::visible()->ordered()->get()->map(fn ($r) => ['title' => $r->title, 'text' => $r->text])->all();
    }

    public function cases(): array
    {
        $rows = CaseStudy::visible()->ordered()->get();
        $photos = $this->media(CaseStudy::class, 'photos');
        $covers = $this->media(CaseStudy::class, 'cover');

        return $rows->map(function (CaseStudy $r) use ($photos, $covers) {
            $list = $this->keys($photos, $r);

            return [
                'slug' => $r->slug,
                'direction' => $r->direction,
                'title' => $r->title,
                'client' => $r->client,
                'date' => $r->date_label,
                'people' => $r->people,
                'request' => $r->request,
                'plan' => implode(' → ', $r->plan ?? []),
                'plan_steps' => array_values($r->plan ?? []),
                'result' => $r->result,
                'review' => $r->review,
                'cover' => $this->keys($covers, $r)[0] ?? $list[0] ?? self::NO_IMAGE,
                'photos' => $list,
                'demo' => (bool) $r->demo,
            ];
        })->all();
    }

    public function findCase(string $slug): ?array
    {
        return collect($this->cases())->firstWhere('slug', $slug);
    }

    public function events(?int $limit = null): array
    {
        $query = Event::visible()->orderByDesc('date')->orderBy('sort')->orderBy('id');
        if ($limit !== null) {
            $query->limit($limit);
        }
        $photos = $this->media(Event::class, 'photos');

        return $query->get()->map(fn (Event $r) => [
            'slug' => $r->slug,
            'title' => $r->title,
            'date' => $r->date->toDateString(),
            'client' => $r->client,
            'description' => $r->description,
            'photos' => $this->keys($photos, $r),
            'demo' => (bool) $r->demo,
        ])->all();
    }

    public function findEvent(string $slug): ?array
    {
        return collect($this->events())->firstWhere('slug', $slug);
    }

    public function partners(): array
    {
        $logos = $this->media(Partner::class, 'logo');

        return Partner::visible()->ordered()->get()->map(fn ($r) => [
            'name' => $r->name,
            'short' => $r->short,
            'logo' => $this->keys($logos, $r)[0] ?? null,
            'description' => $r->description,
            'url' => $r->url,
        ])->all();
    }

    public function faq(): array
    {
        return FaqItem::visible()->ordered()->get()->map(fn ($r) => ['q' => $r->question, 'a' => $r->answer])->all();
    }

    public function gallery(): array
    {
        $images = $this->media(GalleryItem::class, 'image');

        return GalleryItem::visible()->ordered()->get()->map(fn ($r) => [
            'image' => $this->keys($images, $r)[0] ?? self::NO_IMAGE,
            'caption' => $r->caption,
        ])->all();
    }

    public function certificates(): array
    {
        $images = $this->media(Certificate::class, 'image');

        return Certificate::visible()->ordered()->get()->map(fn ($r) => [
            'image' => $this->keys($images, $r)[0] ?? self::NO_IMAGE,
            'title' => $r->title,
            'description' => $r->description,
            'year' => $r->year,
        ])->all();
    }

    public function partnersDisplay(): array
    {
        $s = Setting::get('partners_display', []);
        $marquee = (bool) ($s['marquee'] ?? false);

        return ['marquee' => $marquee, 'mobile_only' => $marquee && (bool) ($s['mobile_only'] ?? false)];
    }

    public function contacts(): array
    {
        return Setting::get('contacts', []);
    }

    public function kp(): array
    {
        return Setting::get('kp', []);
    }

    /** Одиночное фото настроек (без владельца). */
    private function slot(string $slot): ?Media
    {
        return Media::query()->whereNull('owner_type')->where('slot', $slot)->orderBy('sort')->orderBy('id')->first();
    }

    /**
     * Фото коллекции для всех владельцев класса одним запросом.
     *
     * @param  class-string<Model>  $class
     * @return Collection<int, Collection<int, Media>> по id владельца
     */
    private function media(string $class, string $collection): Collection
    {
        return Media::query()
            ->where('owner_type', (new $class)->getMorphClass())
            ->where('collection', $collection)
            ->whereNotNull('key')
            ->orderBy('sort')->orderBy('id')
            ->get()->groupBy('owner_id');
    }

    /** @return list<string> */
    private function keys(Collection $grouped, Model $owner): array
    {
        return ($grouped[$owner->getKey()] ?? collect())->pluck('key')->values()->all();
    }
}
