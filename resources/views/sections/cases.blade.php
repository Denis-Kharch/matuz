@php
    $cases = $c->cases();
@endphp
{{-- Кейсы — лента компактных карточек: листается свайпом, мышью (потянуть) и стрелками; фильтр по направлению. --}}
<section id="cases" aria-labelledby="cases-title" class="border-t border-line py-14 md:py-20" x-data="{ dir: 'all' }">
    <div class="wrap">
        <header class="max-w-3xl">
            <p class="kicker mb-4">Кейсы</p>
            <h2 id="cases-title" class="text-[clamp(1.75rem,3.4vw,2.5rem)] text-text">Реальная практика, подтверждённая результатами</h2>
        </header>

        <div x-data="strip" x-init="$watch('dir', () => reset())" class="mt-6 md:mt-8">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex shrink-0 gap-1 rounded-full border border-line bg-surface p-1 text-sm" role="group" aria-label="Направление кейсов">
                    @foreach (['all' => 'Все', 'training' => 'Обучение', 'events' => 'Мероприятия'] as $key => $label)
                        <button type="button" x-on:click="dir = '{{ $key }}'" :aria-pressed="(dir === '{{ $key }}').toString()"
                                class="rounded-full px-4 py-2 transition" :class="dir === '{{ $key }}' ? 'bg-text text-bg' : 'text-muted hover:text-text'">{{ $label }}</button>
                    @endforeach
                </div>
                <x-strip-arrows label="Листать кейсы" class="hidden sm:flex" />
            </div>

            <ul x-ref="track" data-cases-strip
                class="-mx-4 flex gap-3 overflow-x-auto scroll-px-4 px-4 pt-1 pb-4 [scrollbar-width:none] sm:mx-0 sm:scroll-px-0 sm:px-0 [&::-webkit-scrollbar]:hidden"
                :class="dragging ? 'cursor-grabbing select-none' : 'snap-x snap-mandatory sm:cursor-grab'"
                x-on:pointerdown="down($event)" x-on:pointermove="move($event)" x-on:pointerup="up()" x-on:pointercancel="up()"
                x-on:click.capture="guardClick($event)" x-on:dragstart.prevent>
                @foreach ($cases as $case)
                    <li x-show="dir === 'all' || dir === '{{ $case['direction'] }}'" x-transition.opacity class="w-[16.5rem] shrink-0 snap-start sm:w-72">
                        <a href="{{ route('cases.show', $case['slug']) }}"
                           data-case-card data-direction="{{ $case['direction'] }}"
                           x-on:click.prevent="$store.overlayRoute.open('case-{{ $case['slug'] }}', $el.href, '{{ url('/').'/' }}', $el)"
                           class="card group flex h-full flex-col overflow-hidden transition duration-300 hover:-translate-y-1 hover:border-sand/50">
                            <div data-case-thumb class="relative aspect-[4/3] overflow-hidden">
                                <x-picture :src="$case['cover']" alt="" sizes="18rem" img-class="pointer-events-none h-full w-full object-cover transition duration-500 group-hover:scale-[1.05]" />
                                <div aria-hidden="true" class="absolute inset-0 bg-[linear-gradient(to_top,var(--color-surface),transparent_55%)]"></div>
                                <span class="absolute top-3 left-3 rounded-full bg-bg/70 px-2.5 py-1 text-xs text-sand backdrop-blur">{{ $case['direction'] === 'training' ? 'Обучение' : 'Мероприятие' }}</span>
                            </div>
                            <div class="flex flex-1 flex-col p-4 pt-2">
                                <p class="text-xs text-muted">{{ $case['date'] }} · {{ $case['people'] }}</p>
                                <h3 class="mt-2 line-clamp-3 text-[1.05rem] leading-snug text-text">{{ $case['title'] }}</h3>
                                <span class="mt-auto inline-flex items-center gap-2 pt-4 text-sm font-medium text-text/80 transition group-hover:text-accent-2">
                                    Открыть кейс <span aria-hidden="true" class="transition group-hover:translate-x-1">→</span>
                                </span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            <x-strip-progress class="mt-2" />
        </div>

        <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center">
            <x-button variant="quiet" :href="route('events.index')" class="whitespace-normal text-center">Посмотреть актуальные кейсы в ленте событий Андрея</x-button>
            <x-kp-button source="cases" variant="ghost" class="link-draw" />
        </div>
    </div>
</section>

@push('modals')
    @foreach ($cases as $case)
        <x-modal name="case-{{ $case['slug'] }}" :label="'Кейс: '.$case['title']" :fullscreen="true" :animate="false">
            @include('cases.article', ['case' => $case, 'heading' => 'h2'])
        </x-modal>
    @endforeach
@endpush
