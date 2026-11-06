@php
    $kp = $c->kp();
    $contacts = $c->contacts();
    // Заголовок раздела КП с порядковым номером 01, 02, …
    $n = 0;
    $h = function (string $title) use (&$n) {
        $num = str_pad(++$n, 2, '0', STR_PAD_LEFT);

        return '<h3 class="mt-9 mb-4 flex items-center gap-3 text-[0.95rem] font-semibold tracking-[0.08em] text-paper-accent uppercase"><span class="text-paper-warm">'.$num.'</span>'.e($title).'</h3>';
    };
@endphp
<x-modal name="kp-document" label="Коммерческое предложение" :fullscreen="true" :dismissable="false" panel-class="!bg-[#071012]">
    {{-- Верхняя панель --}}
    <div class="sticky top-0 z-10 border-b border-line bg-bg/90 backdrop-blur no-print">
        <div class="mx-auto flex max-w-[52rem] items-center justify-between gap-3 px-4 py-3">
            <p class="hidden truncate text-sm text-muted sm:block">Коммерческое предложение · Андрей Матузов</p>
            <div class="ml-auto flex items-center gap-2">
                <button type="button" class="btn btn-primary min-h-11 px-5" x-on:click="download()" :disabled="busy">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 4v11m0 0-4-4m4 4 4-4M5 20h14"/></svg>
                    <span x-text="busy ? 'Готовлю PDF…' : 'Скачать PDF'">Скачать PDF</span>
                </button>
                <button type="button" class="btn btn-quiet min-h-11 px-5" x-on:click="$store.modals.close('kp-document')">Закрыть</button>
            </div>
        </div>
    </div>

    <div class="px-3 py-6 sm:px-6 sm:py-10">
        {{-- Лист A4 --}}
        <div x-ref="paper" data-kp-document class="kp-paper mx-auto w-full max-w-[52rem] overflow-hidden rounded-lg bg-paper font-sans text-[0.95rem] leading-relaxed text-paper-ink shadow-2xl">
            <div class="h-1.5 bg-[linear-gradient(90deg,var(--color-paper-accent),var(--color-accent))]"></div>
            <div class="p-6 sm:p-10">
                <header class="kp-avoid flex flex-col gap-5 border-b border-[#e3e7e6] pb-6 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-paper-ink font-display text-sm font-semibold text-white">АМ</span>
                        <div>
                            <p class="font-display text-lg font-semibold">Андрей Матузов</p>
                            <p class="text-sm text-paper-muted">{{ $kp['subtitle'] }}</p>
                        </div>
                    </div>
                    <p class="text-sm leading-6 text-paper-muted sm:text-right">
                        {{ $contacts['phone'] }}<br>
                        {{ \Illuminate\Support\Str::after($contacts['vk'], 'https://') }}<br>
                        t.me/{{ \Illuminate\Support\Str::afterLast($contacts['telegram'], '/') }}
                    </p>
                </header>

                <div class="kp-avoid mt-8">
                    <h2 class="font-display text-[1.75rem] leading-tight font-semibold sm:text-3xl">Коммерческое предложение</h2>
                    <p class="mt-2 text-paper-muted [overflow-wrap:anywhere]">
                        для компании <b class="text-paper-ink" x-text="'«' + result.company + '»'"></b><span data-kp-city x-show="result.city"> · город <span x-text="result.city"></span></span> · <span x-text="result.date"></span>
                    </p>
                </div>

                <div class="kp-avoid mt-7 rounded-xl border-l-4 border-accent bg-[#fbf3ea] p-5">
                    <p>{{ $kp['why'] }}</p>
                </div>

                {{-- Каждый заголовок раздела держится вместе с началом раздела (PDF не оставляет заголовок внизу страницы). --}}
                <div class="kp-avoid">
                    {!! $h('Кто проводит') !!}
                    <p>{{ $kp['who'] }}</p>
                </div>

                <div class="kp-avoid">
                    {!! $h('Акцент работы') !!}
                    <p>{{ $kp['focus'] }}</p>
                </div>

                <div class="kp-avoid">
                    {!! $h('Формат работы') !!}
                    <p class="font-medium">{{ implode(' · ', $kp['formats']) }}</p>
                    <p class="mt-3 text-sm text-paper-muted">{{ $kp['formats_note'] }}</p>
                </div>

                <div class="kp-avoid">
                    {!! $h('Стоимость обучения') !!}
                    <table class="w-full text-left text-[0.9rem]">
                        <tbody class="divide-y divide-[#e3e7e6] border-y border-[#e3e7e6]">
                            @foreach ($kp['prices'] as $p)
                                <tr>
                                    <td class="py-3 pr-4 align-top">{{ $p['label'] }}</td>
                                    <td class="py-3 text-right align-top font-semibold whitespace-nowrap">{{ $p['price'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="kp-avoid mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($kp['price_factors'] as $f)
                        <div class="rounded-xl bg-[#f2f5f4] p-4">
                            <p class="font-semibold">{{ $f['title'] }}</p>
                            <p class="mt-1 text-sm text-paper-muted">{{ $f['text'] }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="kp-avoid mt-4 rounded-xl border border-[#e3e7e6] p-4 text-sm"><b>ФЗ № 422.</b> {{ $kp['terms'] }}</p>

                @php($topicRows = array_chunk($kp['topics'], 2, true))
                @foreach ($topicRows as $r => $row)
                    <div class="kp-avoid">
                        @if ($r === 0)
                            {!! $h('Темы обучения и консультирования') !!}
                        @endif
                        <div class="mb-3 grid gap-3 sm:grid-cols-2">
                            @foreach ($row as $i => $t)
                                <div @class(['rounded-xl border border-[#e3e7e6] p-4', 'sm:col-span-2' => count($row) === 1])>
                                    <p class="font-semibold"><span class="text-paper-warm">{{ $i + 1 }}.</span> {{ $t['title'] }}</p>
                                    <ul class="mt-2 space-y-1 text-sm text-paper-muted">
                                        @foreach ($t['items'] as $item)
                                            <li>— {{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @php($ach = $kp['achievements'])
                <div class="kp-avoid">
                    {!! $h('Корпоративные достижения за 3 года') !!}
                    <ul class="space-y-2">
                        @foreach (array_slice($ach, 0, 3) as $a)
                            <li class="flex gap-3"><span class="shrink-0 font-semibold text-accent">•</span><span>{{ $a }}</span></li>
                        @endforeach
                    </ul>
                </div>
                <ul class="mt-2 space-y-2">
                    @foreach (array_slice($ach, 3) as $a)
                        <li class="kp-avoid flex gap-3"><span class="shrink-0 font-semibold text-accent">•</span><span>{{ $a }}</span></li>
                    @endforeach
                </ul>
            </div>
            <div class="kp-avoid bg-paper-ink px-6 py-7 text-center font-display text-xl text-white sm:px-10">{{ $kp['final'] }}</div>
            <p class="px-6 py-4 text-center text-xs text-paper-muted">Матузов Андрей · {{ $contacts['phone'] }} · Санкт-Петербург · Цены актуальны на дату предложения</p>
        </div>

        <div class="no-pdf no-print mx-auto mt-6 flex max-w-[52rem] flex-col items-center gap-2 text-center">
            <button type="button" class="btn btn-primary" x-on:click="download()" :disabled="busy">
                <span x-text="busy ? 'Готовлю PDF…' : 'Скачать PDF'">Скачать PDF</span>
            </button>
            <p class="text-sm text-muted">Файл сохранится на устройство — удобно переслать коллегам.</p>
        </div>
    </div>
</x-modal>
