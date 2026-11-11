{{-- Отдельная страница кейса (прямая ссылка, поисковики): только кейс, без всей главной.
     С главной кейс по-прежнему открывается поверх неё (окно + адрес /cases/…). --}}
@extends('layouts.site')

@section('title', $case['title'].' — кейс · Андрей Матузов')
@section('description', \Illuminate\Support\Str::limit(\App\Support\RichText::plain($case['result']), 155))
@section('og_image', $case['cover'])
@section('og_type', 'article')

@push('head')
    {{ \App\Site\StructuredData::script(app(\App\Site\StructuredData::class)->case($case)) }}
@endpush

@section('content')
    <div class="pt-20 md:pt-24" data-case-page="{{ $case['slug'] }}">
        <nav aria-label="Навигация" class="wrap mb-4 text-sm">
            <ol class="flex flex-wrap items-center gap-2 text-muted">
                <li><a href="{{ route('home') }}" class="transition hover:text-text">Главная</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('home') }}#cases" class="transition hover:text-text">Кейсы</a></li>
                <li aria-hidden="true">/</li>
                <li class="truncate text-text/80" aria-current="page">{{ $case['title'] }}</li>
            </ol>
        </nav>

        @include('cases.article', ['case' => $case, 'heading' => 'h1'])

        <div class="wrap relative z-10 -mt-10 pb-20 md:pb-24">
            <a href="{{ route('home') }}#cases" class="inline-flex items-center gap-2 text-sm font-medium text-text/80 transition hover:text-accent-2">
                <span aria-hidden="true">←</span> Все кейсы
            </a>
        </div>
    </div>
@endsection
