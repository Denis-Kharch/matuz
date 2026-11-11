@php
    $nav = [
        'about' => 'Об Андрее',
        'directions' => 'Направления',
        'cases' => 'Кейсы',
        'events' => 'События',
        'partners' => 'Партнёры',
        'faq' => 'Вопросы',
        'gallery' => 'Галерея',
        'certificates' => 'Регалии',
        'contacts' => 'Контакты',
    ];
    // На внутренних страницах якоря ведут на главную.
    $base = request()->routeIs('home', 'cases.show') ? '' : rtrim(route('home'), '/').'/';
@endphp
<header
    x-data="{ menu: false }"
    x-on:keydown.escape.window="menu = false"
    class="pointer-events-none fixed inset-x-0 top-0 z-50 pt-3"
>
    <div class="intro-down wrap pointer-events-auto">
        {{-- Стеклянная «пилюля» (Liquid Glass), края совпадают с краями контента. --}}
        <div data-header-bar class="glass flex h-14 items-center justify-between gap-6 rounded-full pr-2 pl-2.5 md:h-16 md:pl-3">
            <a href="{{ route('home') }}" class="group flex items-center gap-3" title="На главную">
                <img src="{{ asset('brand/logo-96.webp') }}" alt="MATUZOV.RU" width="40" height="40" class="size-9 rounded-lg ring-1 ring-sand/30 transition group-hover:ring-accent md:size-10">
                <span class="font-display text-[1.05rem] font-semibold tracking-tight text-text">Андрей Матузов</span>
            </a>

            <nav aria-label="Разделы" class="hidden xl:block">
                <ul class="flex items-center gap-0.5">
                    @foreach ($nav as $id => $label)
                        <li>
                            <a href="{{ $base }}#{{ $id }}" data-spy-link
                               class="rounded-full px-3 py-2 text-[0.9rem] text-text/75 transition hover:text-text aria-[current=true]:bg-white/10 aria-[current=true]:text-text">{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <button type="button" class="inline-flex h-10 items-center gap-2 rounded-full px-4 text-sm font-medium text-text transition hover:bg-white/10 xl:hidden"
                    x-on:click="menu = !menu" :aria-expanded="menu" aria-controls="site-menu">
                <span x-text="menu ? 'Закрыть' : 'Меню'">Меню</span>
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path x-show="!menu" d="M4 8h16M4 16h16"/>
                    <path x-show="menu" x-cloak d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>

        <nav id="site-menu" aria-label="Меню сайта" x-show="menu" x-cloak
             x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="glass mt-2 max-h-[calc(100svh-6rem)] overflow-y-auto rounded-[1.75rem] bg-bg/85 px-5 py-2 xl:hidden">
            <ul class="flex flex-col">
                @foreach ($nav as $id => $label)
                    <li>
                        <a href="{{ $base }}#{{ $id }}" x-on:click="menu = false"
                           class="flex items-center justify-between border-b border-white/10 py-4 font-display text-2xl text-text last:border-0">
                            {{ $label }}
                            <span class="text-sand" aria-hidden="true">→</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
</header>
