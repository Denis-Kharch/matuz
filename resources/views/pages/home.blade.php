@extends('layouts.site')

@if ($openCase)
    @section('title', $openCase['title'].' — кейс · Андрей Матузов')
    @section('description', \Illuminate\Support\Str::limit($openCase['result'], 155))
@endif

@section('content')
    @foreach (['hero', 'marquee', 'about', 'directions', 'cases', 'events', 'partners', 'faq', 'gallery', 'certificates', 'contacts'] as $section)
        @includeIf('sections.'.$section)
    @endforeach
@endsection
