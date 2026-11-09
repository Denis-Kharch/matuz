{{-- Событие в ленте. $event, $base — адрес, куда вернуться при закрытии окна. --}}
@php($date = \Illuminate\Support\Carbon::parse($event['date'])->locale('ru'))
<li data-event-item class="group">
    <a href="{{ route('events.show', $event['slug']) }}"
       x-data
       x-on:click.prevent="$store.overlayRoute.open('event-{{ $event['slug'] }}', $el.href, '{{ $base }}')"
       class="grid gap-5 py-8 transition sm:grid-cols-[7.5rem_minmax(0,1fr)] md:grid-cols-[7.5rem_minmax(0,1fr)_minmax(0,22rem)] md:items-center md:gap-8">
        <time datetime="{{ $event['date'] }}" class="flex items-baseline gap-2 sm:block">
            <span class="font-display text-4xl leading-none text-text sm:block">{{ $date->format('d') }}</span>
            <span class="text-sm text-muted sm:mt-2 sm:block">{{ \Illuminate\Support\Str::after($date->translatedFormat('j F Y'), ' ') }}</span>
        </time>
        <div>
            <p class="text-sm text-sand">{{ $event['client'] }}</p>
            <h3 class="mt-2 text-xl leading-snug text-text transition group-hover:text-accent-2 md:text-2xl">{{ $event['title'] }}</h3>
            <p class="mt-3 line-clamp-2 text-muted">{{ \App\Support\RichText::plain($event['description']) }}</p>
        </div>
        <div class="grid grid-cols-3 gap-2 sm:col-start-2 md:col-start-auto">
            @foreach (array_slice($event['photos'], 0, 3) as $photo)
                <div class="aspect-square overflow-hidden rounded-xl ring-1 ring-line">
                    <x-picture :src="$photo" alt="" sizes="(min-width: 768px) 8rem, 30vw" img-class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                </div>
            @endforeach
        </div>
    </a>
</li>
