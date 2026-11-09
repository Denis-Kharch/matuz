<section id="faq" aria-labelledby="faq-title" class="border-t border-line py-20 md:py-28">
    <div class="wrap grid gap-10 md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] md:gap-14">
        <header class="md:sticky md:top-28 md:self-start">
            <p class="kicker mb-4">Вопросы</p>
            <h2 id="faq-title" class="text-[clamp(2rem,4.2vw,3.25rem)] text-text">Частые вопросы к Андрею</h2>
            <p class="mt-5 text-muted">Не нашли ответ? Самый быстрый путь — моментальное КП: в нём цены, форматы и условия.</p>
            <div class="mt-8">
                <x-kp-button source="faq" />
            </div>
        </header>
        <div class="divide-y divide-line border-y border-line" x-data="{ open: null }">
            @foreach ($c->faq() as $i => $f)
                <div>
                    <h3>
                        <button type="button" x-on:click="open = open === {{ $i }} ? null : {{ $i }}"
                                :aria-expanded="(open === {{ $i }}).toString()" aria-expanded="false" aria-controls="faq-{{ $i }}"
                                class="group flex w-full items-center justify-between gap-6 py-5 text-left">
                            <span class="font-display text-lg text-text transition group-hover:text-accent-2">{{ $f['q'] }}</span>
                            <span class="grid size-9 shrink-0 place-items-center rounded-full border border-line text-muted transition" :class="open === {{ $i }} && 'rotate-45 border-accent text-accent-2'">
                                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </button>
                    </h3>
                    <div id="faq-{{ $i }}" x-show="open === {{ $i }}" x-collapse x-cloak>
                        <div class="max-w-2xl pb-6 text-muted">{{ \App\Support\RichText::render($f['a']) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
