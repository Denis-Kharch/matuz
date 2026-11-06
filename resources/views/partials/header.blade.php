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
    x-data="{ solid: false, menu: false }"
    x-init="solid = scrollY > 24; addEventListener('scroll', () => solid = scrollY > 24, { passive: true })"
    x-on:keydown.escape.window="menu = false"
    class="fixed inset-x-0 top-0 z-50 transition-colors duration-300"
    :class="solid || menu ? 'bg-bg/85 backdrop-blur-md border-b border-line/70' : 'bg-gradient-to-b from-bg/70 to-transparent border-b border-transparent'"
>
    <div class="wrap flex h-16 items-center justify-between gap-6 md:h-[4.5rem]">
        <a href="{{ route('home') }}" class="group flex items-center gap-3" title="На главную">
            <span aria-hidden="true" class="grid size-9 place-items-center rounded-full border border-sand/50 font-display text-[0.8rem] font-semibold tracking-wide text-sand transition group-hover:border-accent group-hover:text-accent-2">АМ</span>
            <span class="font-display text-[1.05rem] font-semibold tracking-tight text-text">Андрей Матузов</span>
        </a>

        <nav aria-label="Разделы" class="hidden xl:block">
            <ul class="flex items-center gap-1">
                @foreach ($nav as $id => $label)
                    <li>
                        <a href="{{ $base }}#{{ $id }}" data-spy-link
                           class="rounded-full px-3 py-2 text-[0.9rem] text-muted transition hover:text-text aria-[current=true]:text-text aria-[current=true]:bg-surface-2">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <button type="button" class="xl:hidden -mr-2 inline-flex h-11 items-center gap-2 rounded-full px-3 text-sm font-medium text-text"
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
         class="xl:hidden h-[calc(100svh-4rem)] overflow-y-auto border-t border-line/70 bg-bg">
        <ul class="wrap flex flex-col py-4">
            @foreach ($nav as $id => $label)
                <li>
                    <a href="{{ $base }}#{{ $id }}" x-on:click="menu = false"
                       class="flex items-center justify-between border-b border-line/60 py-4 font-display text-2xl text-text">
                        {{ $label }}
                        <span class="text-sand" aria-hidden="true">→</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</header>
