@php($about = $c->about())
<section id="about" aria-labelledby="about-title" class="py-20 md:py-28">
    <div class="wrap">
        <div class="grid items-center gap-10 md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] md:gap-14 lg:gap-20">
            {{-- Фото в рамке: песочная линия со сдвигом + тёплая подложка. --}}
            <div class="relative mx-auto w-full max-w-md md:max-w-none">
                <div aria-hidden="true" class="absolute inset-0 translate-x-3 translate-y-3 rounded-[1.75rem] border border-sand/40 md:translate-x-5 md:translate-y-5"></div>
                <div class="relative aspect-[4/5] overflow-hidden rounded-[1.75rem] bg-umber/40 ring-1 ring-line">
                    <x-picture :src="$about['photo']" alt="Андрей Матузов ведёт тренинг" sizes="(min-width: 768px) 40vw, 100vw" :focal="['x' => 68, 'y' => 30]" />
                    <div aria-hidden="true" class="absolute inset-0 bg-[linear-gradient(to_top,rgb(10_17_19/0.55),transparent_45%)]"></div>
                </div>
            </div>

            <div>
                <p class="kicker mb-5">Кто такой Андрей Матузов</p>
                <h2 id="about-title" class="text-[clamp(2.4rem,5.4vw,4.25rem)] text-text">{{ $about['name'] }}</h2>
                <p class="mt-6 font-display text-xl leading-snug text-text/90 md:text-2xl">{{ $about['lead'] }}</p>
                <div class="mt-6 space-y-4 text-muted">
                    @foreach ($about['paragraphs'] as $p)
                        <p>{{ $p }}</p>
                    @endforeach
                </div>
                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <span class="inline-flex items-center gap-2 rounded-full border border-line bg-surface px-4 py-2 text-sm text-text">
                        <svg viewBox="0 0 24 24" class="size-4 text-sand" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                        Основной город: {{ $about['city'] }}
                    </span>
                    <x-kp-button source="about" />
                </div>
            </div>
        </div>

        {{-- Опыт Андрея: картинка всегда слева. --}}
        <div class="mt-24 md:mt-32">
            <h3 class="text-[clamp(1.75rem,3.2vw,2.5rem)] text-text">Опыт Андрея</h3>
            <ol class="mt-10 divide-y divide-line border-y border-line">
                @foreach ($c->experience() as $i => $stage)
                    <li class="grid gap-6 py-8 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] sm:items-center sm:gap-10 md:py-10 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
                        <div data-stage-image="left" class="group relative aspect-[16/10] overflow-hidden rounded-[var(--radius-card)] ring-1 ring-line">
                            <x-picture :src="$stage['image']" alt="" sizes="(min-width: 640px) 40vw, 100vw" img-class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                        </div>
                        <div>
                            <p class="flex items-baseline gap-3 font-display text-sm tracking-wide text-sand">
                                <span class="text-text/40">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                {{ $stage['period'] ?? 'Сегодня' }}
                            </p>
                            <h4 class="mt-3 text-2xl text-text md:text-[1.9rem]">{{ $stage['title'] }}</h4>
                            <p class="mt-4 max-w-2xl text-muted">{{ $stage['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
