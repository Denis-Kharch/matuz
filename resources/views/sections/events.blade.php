@php($events = $c->events(6))
<section id="events" aria-labelledby="events-title" class="border-t border-line py-20 md:py-28">
    <div class="wrap">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <header class="max-w-3xl">
                <p class="kicker mb-4">События</p>
                <h2 id="events-title" class="text-[clamp(2rem,4.2vw,3.25rem)] text-text">Лента последних событий</h2>
            </header>
            <x-button variant="quiet" :href="route('events.index')" class="self-start md:self-auto">Открыть всю ленту</x-button>
        </div>
        <ul class="mt-10 divide-y divide-line border-y border-line">
            @foreach ($events as $event)
                @include('events.item', ['event' => $event, 'base' => route('home')])
            @endforeach
        </ul>
        <div class="mt-8">
            <x-kp-button source="events" variant="ghost" class="link-draw" />
        </div>
    </div>
</section>

@push('modals')
    @foreach ($events as $event)
        @include('events.overlay', ['event' => $event])
    @endforeach
@endpush
