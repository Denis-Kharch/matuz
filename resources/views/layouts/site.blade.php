@php
    $defaultTitle = 'Андрей Матузов — бизнес-тренер, T&D-партнёр, спикер · Санкт-Петербург';
    $title = trim($__env->yieldContent('title')) ?: $defaultTitle;
    $description = trim($__env->yieldContent('description')) ?: 'Корпоративное обучение под задачи бизнеса: анализ, разработка, тренинги по продажам, переговорам и управлению, организация стратегических сессий и форумов. Моментальное КП — по названию компании.';
    $ogImage = asset('img/'.$c->hero()['image'].'-1280.webp');
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
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @if (config('site.preview'))
        <script>try{var f=localStorage.getItem('am_fonts');if(f==='b')document.documentElement.dataset.fonts='b'}catch(e){}</script>
    @endif
    @if (request()->routeIs('home', 'cases.show'))
        {{-- LCP: фото первого экрана грузится сразу. --}}
        @php($heroImg = $c->hero()['image'])
        <link rel="preload" as="image" type="image/avif" fetchpriority="high"
              imagesrcset="{{ collect(\App\Support\ImageManifest::get($heroImg)['widths'])->map(fn ($w) => asset("img/{$heroImg}-{$w}.avif")." {$w}w")->implode(', ') }}"
              imagesizes="100vw">
    @endif
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-svh overflow-x-clip">
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
        <div data-font-switch x-data="fontSwitch" class="fixed top-[4.5rem] left-4 z-[45] flex sm:top-auto sm:bottom-5 sm:left-5 items-center gap-1 rounded-full border border-line bg-surface/90 p-1 text-xs backdrop-blur" role="group" aria-label="Шрифты (превью)">
            <span class="hidden px-2 text-muted sm:inline">Шрифты</span>
            <button type="button" class="rounded-full px-3 py-1.5" :class="pair === 'a' ? 'bg-text text-bg' : 'text-muted hover:text-text'" x-on:click="set('a')">A</button>
            <button type="button" class="rounded-full px-3 py-1.5" :class="pair === 'b' ? 'bg-text text-bg' : 'text-muted hover:text-text'" x-on:click="set('b')">B</button>
        </div>
    @endif
</body>
</html>
