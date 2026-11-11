{{-- Полноэкранное окно события. $event --}}
@php($date = \Illuminate\Support\Carbon::parse($event['date'])->locale('ru'))
<x-modal name="event-{{ $event['slug'] }}" label="Событие: {{ $event['title'] }}" :fullscreen="true">
    <article class="wrap py-20 md:py-24">
        <p class="kicker">Лента событий</p>
        @if ($event['demo'] && config('site.preview'))
            <span class="ml-3 rounded-full border border-accent/50 px-2.5 py-0.5 text-xs text-accent-2">Демо-событие — будет заменено</span>
        @endif
        <h2 class="mt-5 max-w-4xl text-[clamp(2rem,5vw,3.75rem)] text-text">{{ $event['title'] }}</h2>
        <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-4 border-y border-line py-5 text-sm">
            <div><dt class="text-muted">Дата</dt><dd class="mt-1 font-display text-lg text-text"><time datetime="{{ $event['date'] }}">{{ $date->translatedFormat('j F Y') }}</time></dd></div>
            <div><dt class="text-muted">Заказчик</dt><dd class="mt-1 font-display text-lg text-text">{{ $event['client'] }}</dd></div>
        </dl>
        <div class="mt-10 max-w-3xl text-xl leading-relaxed text-text/90">{{ \App\Support\RichText::render($event['description']) }}</div>
        <ul @class(['mt-12 grid gap-3', 'sm:grid-cols-2 lg:grid-cols-3' => count($event['photos']) !== 4, 'sm:grid-cols-2' => count($event['photos']) === 4])>
            @foreach ($event['photos'] as $photo)
                <li class="aspect-[4/3] overflow-hidden rounded-[var(--radius-card)] ring-1 ring-line">
                    <x-picture :src="$photo" :alt="$event['title'].' — фото '.$loop->iteration" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" />
                </li>
            @endforeach
        </ul>
        <div class="mt-12">
            <x-kp-button source="event" variant="primary" />
        </div>
    </article>
</x-modal>
