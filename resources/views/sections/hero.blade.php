@php
    $hero = $c->hero();
    $contacts = $c->contacts();
    $copySide = $hero['subject_side'] === 'left' ? 'right' : 'left';
@endphp
<section id="top" class="relative isolate flex min-h-svh flex-col overflow-hidden" aria-label="Андрей Матузов">
    <div data-hero-img class="absolute inset-0 -z-10">
        <x-picture :src="$hero['image']" :mobile="$hero['image_mobile']" alt="Андрей Матузов выступает на сцене форума" :eager="true" :focal="$hero['focal']" class="block h-full w-full" />
    </div>
    {{-- Затемнение: со стороны текста и снизу (на телефоне — только снизу). --}}
    <div aria-hidden="true" @class([
        'absolute inset-0 -z-10 hidden md:block',
        'bg-[linear-gradient(to_left,var(--color-bg)_8%,rgb(10_17_19/0.82)_38%,rgb(10_17_19/0.15)_68%,transparent)]' => $copySide === 'right',
        'bg-[linear-gradient(to_right,var(--color-bg)_8%,rgb(10_17_19/0.82)_38%,rgb(10_17_19/0.15)_68%,transparent)]' => $copySide === 'left',
    ])></div>
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-[linear-gradient(to_top,var(--color-bg)_4%,rgb(10_17_19/0.88)_42%,rgb(10_17_19/0.1)_72%,transparent)] md:bg-[linear-gradient(to_top,var(--color-bg),transparent_28%)]"></div>

    <div class="wrap flex flex-1 items-end pt-24 pb-14 md:items-center md:pb-20">
        <div data-copy-side="{{ $copySide }}" @class(['w-full md:max-w-[34rem] lg:max-w-[40rem]', 'md:ml-auto' => $copySide === 'right'])>
            <p class="kicker mb-5 hidden sm:inline-flex">Бизнес-тренер · T&amp;D-партнёр · спикер</p>
            <h1 class="text-[clamp(1.9rem,3.9vw,3.4rem)] text-text">{{ str_replace(' — ', "\u{00A0}— ", $hero['title']) }}</h1>
            <p class="mt-4 max-w-[30rem] text-base text-text/80 sm:mt-5 sm:text-lg md:text-xl">{{ $hero['subtitle'] }}</p>

            <div class="mt-7 flex flex-col gap-4 sm:mt-9 sm:flex-row sm:items-center sm:justify-between sm:gap-5">
                <x-kp-button source="hero" variant="primary" class="w-full sm:w-auto" />
                <ul class="flex items-center gap-2" aria-label="Контакты">
                    @foreach ([
                        ['href' => $contacts['phone_href'], 'label' => 'Позвонить: '.$contacts['phone'], 'icon' => 'phone'],
                        ['href' => $contacts['telegram'], 'label' => 'Telegram', 'icon' => 'telegram'],
                        ['href' => $contacts['vk'], 'label' => 'ВКонтакте', 'icon' => 'vk'],
                        ['href' => $contacts['max'], 'label' => 'MAX', 'icon' => 'max'],
                    ] as $link)
                        <li>
                            <a href="{{ $link['href'] }}" aria-label="{{ $link['label'] }}" title="{{ $link['label'] }}"
                               @unless (str_starts_with($link['href'], 'tel:')) target="_blank" rel="noopener" @endunless
                               class="grid size-12 place-items-center rounded-full border border-sand/35 bg-bg/40 text-text backdrop-blur transition hover:-translate-y-0.5 hover:border-accent hover:text-accent-2">
                                @include('partials.icon', ['name' => $link['icon'], 'class' => 'size-5'])
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="mt-5 flex items-start gap-2.5 text-[0.8125rem] text-muted sm:mt-6 sm:text-sm">
                <svg viewBox="0 0 24 24" class="mt-0.5 size-4 shrink-0 text-sand" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3"/></svg>
                {{ $hero['note'] }}
            </p>
        </div>
    </div>
</section>
