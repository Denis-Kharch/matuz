@php
    $cases = $c->cases();
    $dirLabel = ['training' => 'Обучение и спикерство', 'events' => 'Публичные мероприятия'];
@endphp
<section id="cases" aria-labelledby="cases-title" class="border-t border-line py-20 md:py-28" x-data="{ dir: 'all' }">
    <div class="wrap">
        <div class="flex flex-col gap-8 md:flex-row md:items-end md:justify-between">
            <header class="max-w-3xl">
                <p class="kicker mb-4">Кейсы</p>
                <h2 id="cases-title" class="text-[clamp(2rem,4.2vw,3.25rem)] text-text">Реальная практика, подтверждённая результатами</h2>
            </header>
            <div class="flex shrink-0 gap-1 rounded-full border border-line bg-surface p-1 text-sm" role="group" aria-label="Направление кейсов">
                @foreach (['all' => 'Все', 'training' => 'Обучение', 'events' => 'Мероприятия'] as $key => $label)
                    <button type="button" x-on:click="dir = '{{ $key }}'" :aria-pressed="(dir === '{{ $key }}').toString()"
                            class="rounded-full px-4 py-2 transition" :class="dir === '{{ $key }}' ? 'bg-text text-bg' : 'text-muted hover:text-text'">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <ul class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($cases as $case)
                <li x-show="dir === 'all' || dir === '{{ $case['direction'] }}'" x-transition.opacity>
                    <a href="{{ route('cases.show', $case['slug']) }}"
                       data-case-card data-direction="{{ $case['direction'] }}"
                       x-on:click.prevent="$store.overlayRoute.open('case-{{ $case['slug'] }}', $el.href, '{{ url('/').'/' }}', $el)"
                       class="card group flex h-full flex-col overflow-hidden transition duration-300 hover:-translate-y-0.5 hover:border-sand/50">
                        <div data-case-thumb class="relative aspect-[16/10] overflow-hidden">
                            <x-picture :src="$case['photos'][0]" alt="" sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw" img-class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                            <div aria-hidden="true" class="absolute inset-0 bg-[linear-gradient(to_top,var(--color-surface),transparent_60%)]"></div>
                            <span class="absolute top-3 left-3 rounded-full bg-bg/70 px-2.5 py-1 text-xs text-sand backdrop-blur">{{ $case['direction'] === 'training' ? 'Обучение' : 'Мероприятие' }}</span>
                        </div>
                        <div class="flex flex-1 flex-col p-5 pt-3">
                            <p class="text-sm text-muted">{{ $case['date'] }} · {{ $case['people'] }}</p>
                            <h3 class="mt-3 text-xl leading-snug text-text">{{ $case['title'] }}</h3>
                            <span class="mt-auto inline-flex items-center gap-2 pt-6 text-sm font-medium text-text/80 transition group-hover:text-accent-2">
                                Открыть кейс <span aria-hidden="true" class="transition group-hover:translate-x-0.5">→</span>
                            </span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-10 flex flex-col gap-4 sm:flex-row sm:items-center">
            <x-button variant="quiet" :href="route('events.index')" class="whitespace-normal text-center">Посмотреть актуальные кейсы в ленте событий Андрея</x-button>
            <x-kp-button source="cases" variant="ghost" class="link-draw" />
        </div>
    </div>
</section>

@if ($openCase ?? null)
    <div hidden data-open-overlay="case-{{ $openCase['slug'] }}" data-overlay-base="{{ url('/').'/' }}"></div>
@endif

@push('modals')
    @foreach ($cases as $case)
        <x-modal name="case-{{ $case['slug'] }}" :label="'Кейс: '.$case['title']" :fullscreen="true" :animate="false">
            {{-- Баннер: в него «вырастает» фото карточки (View Transitions). --}}
            <div data-case-banner class="relative h-[38svh] min-h-56 w-full overflow-hidden md:h-[46svh]">
                <x-picture :src="$case['photos'][0]" alt="" sizes="100vw" />
                <div aria-hidden="true" class="absolute inset-0 bg-[linear-gradient(to_top,var(--color-bg),rgb(10_17_19/0.35)_45%,rgb(10_17_19/0.15))]"></div>
            </div>
            <article class="wrap relative -mt-20 pb-20 md:-mt-28 md:pb-24">
                <p class="kicker">{{ $dirLabel[$case['direction']] }}</p>
                @if ($case['demo'] && config('site.preview'))
                    <span class="ml-3 rounded-full border border-accent/50 px-2.5 py-0.5 text-xs text-accent-2">Демо-кейс — будет заменён</span>
                @endif
                <h2 class="mt-5 max-w-4xl text-[clamp(2rem,5vw,4rem)] text-text">{{ $case['title'] }}</h2>
                <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-4 border-y border-line py-5 text-sm">
                    <div><dt class="text-muted">Дата</dt><dd class="mt-1 font-display text-lg text-text">{{ $case['date'] }}</dd></div>
                    <div><dt class="text-muted">Участники</dt><dd class="mt-1 font-display text-lg text-text">{{ $case['people'] }}</dd></div>
                    <div><dt class="text-muted">Заказчик</dt><dd class="mt-1 font-display text-lg text-text">{{ $case['client'] }}</dd></div>
                </dl>

                <div class="mt-12 grid gap-12 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:gap-16">
                    <div class="space-y-10">
                        <section>
                            <h3 class="kicker">Запрос заказчика</h3>
                            <p class="mt-4 font-display text-2xl leading-snug text-text">«{{ $case['request'] }}»</p>
                        </section>
                        <section>
                            <h3 class="kicker">План мероприятия</h3>
                            <ol class="mt-5 space-y-3">
                                @foreach (preg_split('/\s*→\s*/u', $case['plan']) as $n => $step)
                                    <li class="flex gap-4 text-text/85"><span class="font-display text-sand">{{ $n + 1 }}</span><span>{{ \Illuminate\Support\Str::ucfirst($step) }}</span></li>
                                @endforeach
                            </ol>
                        </section>
                        <section class="rounded-[var(--radius-card)] border border-accent/30 bg-[linear-gradient(135deg,rgb(237_153_84/0.10),transparent_60%)] p-6">
                            <h3 class="kicker !text-accent-2">Результат мероприятия</h3>
                            <p class="mt-4 text-lg text-text">{{ $case['result'] }}</p>
                        </section>
                    </div>
                    <aside>
                        <figure class="card p-7 lg:sticky lg:top-24">
                            <h3 class="kicker">Отзыв заказчика</h3>
                            <blockquote class="mt-5 font-display text-xl leading-relaxed text-text">«{{ $case['review'] }}»</blockquote>
                            <figcaption class="mt-5 text-sm text-muted">{{ $case['client'] }}</figcaption>
                        </figure>
                    </aside>
                </div>

                <ul class="mt-14 grid gap-3 sm:grid-cols-3">
                    @foreach ($case['photos'] as $photo)
                        <li class="aspect-[4/3] overflow-hidden rounded-[var(--radius-card)] ring-1 ring-line">
                            <x-picture :src="$photo" alt="" sizes="(min-width: 640px) 33vw, 100vw" />
                        </li>
                    @endforeach
                </ul>

                <div class="mt-12">
                    <x-kp-button source="case" variant="primary" />
                </div>
            </article>
        </x-modal>
    @endforeach
@endpush
