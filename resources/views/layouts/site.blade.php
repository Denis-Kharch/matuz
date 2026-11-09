@php
    $defaultTitle = 'Андрей Матузов — бизнес-тренер, T&D-партнёр, спикер · Санкт-Петербург';
    $title = trim($__env->yieldContent('title')) ?: $defaultTitle;
    $description = trim($__env->yieldContent('description')) ?: 'Корпоративное обучение под задачи бизнеса: анализ, разработка, тренинги по продажам, переговорам и управлению, организация стратегических сессий и форумов. Моментальное КП — по названию компании.';
    $ogImage = \App\Support\ImageManifest::url($c->hero()['image'], 'webp', 1280);
@endphp
<!DOCTYPE html>
<html lang="ru" class="bg-bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#0a1113">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="Андрей Матузов">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    {{-- Иконки: favicon.ico в корне (его по умолчанию берут Яндекс и Google), PNG кратные 48 (Google),
         120×120 (Яндекс), apple-touch и манифест. Генерирует scripts/brand.mjs. --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('brand/favicon-32.png') }}" type="image/png" sizes="32x32">
    <link rel="icon" href="{{ asset('brand/favicon-48.png') }}" type="image/png" sizes="48x48">
    <link rel="icon" href="{{ asset('brand/favicon-96.png') }}" type="image/png" sizes="96x96">
    <link rel="icon" href="{{ asset('brand/favicon-120.png') }}" type="image/png" sizes="120x120">
    <link rel="apple-touch-icon" href="{{ asset('brand/apple-touch-icon.png') }}" sizes="180x180">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    @if (\App\Site\Indexing::closed())
        {{-- Превью на GitHub Pages живёт в подпапке, где robots.txt не читается, — закрываем от индексации мета-тегом. --}}
        <meta name="robots" content="noindex, nofollow">
    @endif
    @if (config('site.preview'))
        <script>try{var f=localStorage.getItem('am_fonts');if(f==='b')document.documentElement.dataset.fonts='b'}catch(e){}</script>
    @endif
    @if (request()->routeIs('home', 'cases.show'))
        {{-- LCP: фото первого экрана грузится сразу. --}}
        @php($heroMeta = \App\Support\ImageManifest::get($c->hero()['image']))
        @if ($heroMeta['widths'] && in_array('avif', $heroMeta['formats']))
            <link rel="preload" as="image" type="image/avif" fetchpriority="high"
                  imagesrcset="{{ collect($heroMeta['widths'])->map(fn ($w) => asset("{$heroMeta['base']}-{$w}.avif")." {$w}w")->implode(', ') }}"
                  imagesizes="100vw">
        @endif
    @endif
    @if (config('site.kp_endpoint'))
        <meta name="kp-endpoint" content="{{ url(config('site.kp_endpoint')) }}">
    @endif
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-svh overflow-x-clip">
    {{-- Liquid Glass: фильтр преломления для backdrop-filter (используется только в Chromium, см. app.js). --}}
    <svg width="0" height="0" class="absolute" aria-hidden="true" focusable="false">
        <filter id="liquid-glass" x="0%" y="0%" width="100%" height="100%" color-interpolation-filters="sRGB">
            <feTurbulence type="fractalNoise" baseFrequency="0.008 0.012" numOctaves="2" seed="7" result="noise"/>
            <feGaussianBlur in="noise" stdDeviation="2" result="soft"/>
            <feDisplacementMap in="SourceGraphic" in2="soft" scale="36" xChannelSelector="R" yChannelSelector="G"/>
        </filter>
    </svg>

    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-full focus:bg-accent focus:px-4 focus:py-2 focus:text-on-accent">Перейти к содержанию</a>

    @include('partials.header')

    <main id="main">
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.floating-kp')
    @include('partials.cookie')
    @stack('modals')
    @include('kp.form')

    @if (config('site.preview'))
        <div data-font-switch x-data="fontSwitch" class="glass fixed top-[5.25rem] left-4 z-[45] flex items-center gap-1 rounded-full p-1 text-xs sm:top-auto sm:bottom-5 sm:left-5" role="group" aria-label="Шрифты (превью)">
            <span class="hidden px-2 text-muted sm:inline">Шрифты</span>
            <button type="button" class="rounded-full px-3 py-1.5" :class="pair === 'a' ? 'bg-text text-bg' : 'text-muted hover:text-text'" x-on:click="set('a')">A</button>
            <button type="button" class="rounded-full px-3 py-1.5" :class="pair === 'b' ? 'bg-text text-bg' : 'text-muted hover:text-text'" x-on:click="set('b')">B</button>
        </div>
    @endif
</body>
</html>
