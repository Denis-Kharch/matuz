@extends('layouts.site')

@section('title', 'Страница не найдена · Андрей Матузов')

@section('content')
    <section class="flex min-h-[80svh] items-center pt-24">
        <div class="wrap max-w-2xl">
            <p class="kicker mb-5">Ошибка 404</p>
            <h1 class="text-[clamp(2.25rem,5vw,3.75rem)] text-text">Страница не найдена</h1>
            <p class="mt-5 text-lg text-muted">Возможно, ссылка устарела. Всё главное — на главной странице, а КП можно получить за минуту.</p>
            <div class="mt-9 flex flex-wrap gap-3">
                <x-button :href="route('home')">На главную</x-button>
                <x-kp-button source="404" />
            </div>
        </div>
    </section>
@endsection
