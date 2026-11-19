@extends('layouts.site')

@push('head')
    {{ \App\Site\StructuredData::script(app(\App\Site\StructuredData::class)->home()) }}
@endpush

@section('content')
    {{-- Первый экран всегда первый; остальные блоки — в порядке из админки (Страница → Блоки главной), скрытые не выводятся. --}}
    @include('sections.hero')
    @foreach ($c->homeSections() as $section)
        @if ($section['visible'])
            @includeIf('sections.'.$section['key'])
        @endif
    @endforeach
@endsection
