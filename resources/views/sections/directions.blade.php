@php
    $formats = $c->formats();
    $plus = '<svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>';
@endphp
<section id="directions" aria-labelledby="directions-title" class="border-t border-line bg-[radial-gradient(60rem_30rem_at_85%_0%,rgb(7_100_101/0.18),transparent_70%)] py-20 md:py-28"
         x-data="{ current: 0, formats: @js($formats) }">
    <div class="wrap">
        <header class="max-w-3xl">
            <p class="kicker mb-4">Чем я могу быть полезен</p>
            <h2 id="directions-title" class="text-[clamp(2rem,4.2vw,3.25rem)] text-text">Направления работы</h2>
        </header>

        {{-- 01. Обучение и спикерство --}}
        <div class="mt-14 md:mt-20">
            <div class="grid gap-6 md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] md:gap-14">
                <h3 class="flex items-baseline gap-4 text-[clamp(1.75rem,3.2vw,2.5rem)] text-text">
                    <span class="font-display text-base text-sand">01</span>Обучение и спикерство
                </h3>
                <p class="text-lg text-muted md:pt-2">{{ $c->trainingIntro() }}</p>
            </div>

            <h4 class="mt-14 mb-6 text-xl text-text">Ознакомьтесь с форматами обучений</h4>
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($formats as $i => $f)
                    <li>
                        <button type="button" data-format-card
                                x-on:click="current = {{ $i }}; $store.modals.open('format')"
                                class="card group flex h-full w-full flex-col p-5 text-left transition duration-300 hover:-translate-y-0.5 hover:border-sand/50 hover:bg-surface-2">
                            <span class="flex items-center justify-between text-sm">
                                <span class="font-display text-sand">Формат {{ $f['n'] }}</span>
                                <span class="text-muted transition group-hover:translate-x-0.5 group-hover:text-accent-2" aria-hidden="true">→</span>
                            </span>
                            <span class="mt-6 font-display text-lg leading-snug text-text">{{ $f['title'] }}</span>
                            <span class="mt-auto flex flex-wrap gap-1.5 pt-5">
                                @foreach ($f['tags'] as $tag)
                                    <span class="rounded-full border border-line px-2.5 py-1 text-xs text-muted">{{ $tag }}</span>
                                @endforeach
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>

            <h4 class="mt-16 mb-6 text-xl text-text">Ознакомьтесь с темами обучений</h4>
            <div class="divide-y divide-line border-y border-line">
                @foreach ($c->topicGroups() as $gi => $group)
                    <div x-data="{ open: false }">
                        <h5>
                            <button type="button" x-on:click="open = !open" :aria-expanded="open.toString()" aria-expanded="false" aria-controls="topic-{{ $gi }}"
                                    class="group flex w-full items-center justify-between gap-6 py-6 text-left">
                                <span class="flex items-baseline gap-4">
                                    <span class="font-display text-sm text-sand">{{ str_pad($gi + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="font-display text-xl text-text transition group-hover:text-accent-2 md:text-2xl">{{ $group['title'] }}</span>
                                </span>
                                <span class="flex items-center gap-4">
                                    <span class="hidden text-sm text-muted sm:inline">{{ count($group['items']) }} {{ \App\Support\Ru::plural(count($group['items']), ['тема', 'темы', 'тем']) }}</span>
                                    <span class="grid size-10 place-items-center rounded-full border border-line text-muted transition group-hover:border-sand/60" :class="open && 'rotate-45 border-accent text-accent-2'">{!! $plus !!}</span>
                                </span>
                            </button>
                        </h5>
                        <div id="topic-{{ $gi }}" x-show="open" x-collapse x-cloak>
                            <ul class="grid gap-3 pb-8 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($group['items'] as $item)
                                    <li class="rounded-2xl bg-surface p-5 ring-1 ring-line">
                                        <p class="font-display text-[1.05rem] leading-snug text-text">{{ $item['title'] }}</p>
                                        <p class="mt-2 text-[0.95rem] text-muted">{{ $item['text'] }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-8">
                <x-kp-button source="training" />
            </div>
        </div>

        {{-- 02. Организация публичных мероприятий --}}
        <div class="mt-24 grid gap-10 md:mt-32 md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] md:gap-14" x-data="{ open: null }">
            <div class="md:sticky md:top-28 md:self-start">
                <h3 class="flex items-baseline gap-4 text-[clamp(1.75rem,3.2vw,2.5rem)] text-text">
                    <span class="font-display text-base text-sand">02</span>Организация публичных мероприятий
                </h3>
                <p class="mt-5 text-lg text-muted">От стратегической сессии до форума: беру на себя процесс целиком. Раскройте пункт — там описано, как он устроен.</p>
                <div class="mt-8">
                    <x-kp-button source="events-org" />
                </div>
            </div>
            <div class="divide-y divide-line border-y border-line">
                @foreach ($c->eventServices() as $si => $service)
                    <div>
                        <h4>
                            <button type="button" x-on:click="open = open === {{ $si }} ? null : {{ $si }}"
                                    :aria-expanded="(open === {{ $si }}).toString()" aria-expanded="false" aria-controls="service-{{ $si }}"
                                    class="group flex w-full items-center justify-between gap-6 py-5 text-left">
                                <span class="font-display text-lg text-text transition group-hover:text-accent-2 md:text-xl">{{ $service['title'] }}</span>
                                <span class="grid size-9 shrink-0 place-items-center rounded-full border border-line text-muted transition" :class="open === {{ $si }} && 'rotate-45 border-accent text-accent-2'">{!! $plus !!}</span>
                            </button>
                        </h4>
                        <div id="service-{{ $si }}" x-show="open === {{ $si }}" x-collapse x-cloak>
                            <p class="max-w-2xl pb-6 text-muted">{{ $service['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <x-modal name="format" label="Формат обучения">
        <p class="font-display text-sm text-sand" x-text="'Формат ' + formats[current].n"></p>
        <h3 class="mt-3 pr-10 text-2xl text-text md:text-3xl" x-text="formats[current].title"></h3>
        <div class="mt-4 flex flex-wrap gap-1.5">
            <template x-for="tag in formats[current].tags" :key="tag">
                <span class="rounded-full border border-line px-2.5 py-1 text-xs text-muted" x-text="tag"></span>
            </template>
        </div>
        <p class="mt-5 text-text/85" x-text="formats[current].description"></p>
        <div class="mt-8">
            <x-kp-button source="format" variant="primary" />
        </div>
    </x-modal>
</section>
